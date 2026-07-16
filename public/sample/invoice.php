<?php
 include("../config/config.php");
 include("sql.php");
require('writetag/WriteTag.php');

$getinivals    =  parse_ini_file("../config/appsettings.ini",true);
//var_dump($getinivals);
$grpbyinsi = $getinivals["print"]["groupby_in_si"];
$getinivalues    =  parse_ini_file("../config/appsettings.ini",true);
$includevatandlf = $getinivalues["print"]["include_vat_lf_in_gt_sale"]; 
 
$showVATledger = $getinivalues["print"]["showvatasledgerinprint_saleinv"]; 
$showLFledger = $getinivalues["print"]["showlfasledgerinprint_saleinv"]; 
$ledvaltoincludeingt = $getinivalues["print"]["include_in_gt_sale"];

$isVATandLFincluded = false;
$newGrandTotal=0;
$roffval=0;

function calculateRoundoff($value) {
    // Convert the value to a float
    $value = floatval($value);

    // Get the integer part of the value
    $integerPart = floor($value);

    // Subtract the integer part from the value to get the decimal part
    $decimalPart = $value - $integerPart;

    // Return the absolute value of the decimal part
    return round(abs($decimalPart * 100), 2);
}

$color=''; 
//Header
function CustomHeader($pdf){

    //,$companyName,$companyAddress,$companyEmail,$companyTin,$companyPan,$billDate,$partyName,$partyPan,$partyTin,$permitNo,$permitDate,$vchNo,$tpNo,$validDate

    $pdf->Line(5, 5, 205, 5);
    $pdf->Line(5, 5, 5, 35);
    $pdf->Line(5, 35, 205, 35);
    $pdf->Line(205, 35, 205, 5);

    $title="INVOICE";

    $pdf->SetFont('ANC', '', 12);
    $pdf->Cell(200, 0, $title, 0, 0, 'C');
    $pdf->Ln(8);

    //$company    =   "M/S UMPOHLIEW CENTRAL WAREHOUSE";

    $pdf->SetFont('ANC', 'B', 22);
    $pdf->Cell(200, 0, $GLOBALS['companyName'], 0, 0, 'C');
    $pdf->Ln(8);

    //$address    =   "9th Mile, Baridua, Ri-Bhoi District";

    $pdf->SetFont('ANC', '', 11);
    $pdf->Cell(200, 0, $GLOBALS['companyAddress'], 0, 0, 'C');
    $pdf->Ln(6);

    $pdf->SetXY(5,32);

    //$pan    =   "sdfsdfsd";
    $pdf->Cell(60, 0, 'PAN : '.$GLOBALS['companyPan'], 0, 0, 'L');

    //$tin    =   "121313113";
    $pdf->Cell(65, 0, 'TIN :'.$GLOBALS['companyTin'], 0, 0, 'C');

    //$email    =   "fsdsdfdfmail@mail.com";
    $pdf->Cell(75, 0, 'E-Mail : '.$GLOBALS['companyEmail'], 0, 0, 'R');

    //Bill Info

    /* $billNo =   "ABC123";
    $tpNo   =   "TP13";
    $permitNo   =   "PERM1345";
    $permitDate =   "26-04-2023";
    $validDate =   "26-04-2023";
 */
    $pdf->SetXY(6,39);


    $pdf->SetStyle("span","ANC","N",11,"0,0,0",0);
    $pdf->SetStyle("b","ANC","B",11,"0,0,0");

    // Permit No
    $color = "0,0,0";
    $pdf->SetLineWidth(0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetDrawColor(255,255,255);

    $pdf->SetStyle("span","ANC","N",11,$color); //SetStyle($tag, $family, $style, $size, $color, $indent=-1, $bullet='')
    $pdf->SetStyle("b","ANC","B",11,$color);

    $txt="<span>Bill No :<b>".$GLOBALS['vchNo']."</b></span>";
    $pdf->WriteTag(65,0,$txt,0,"L",0,0);

    $txt="<span>T Pass No :<b>".$GLOBALS['tpNo']."</b></span>";
    $pdf->WriteTag(55,0,$txt,0,"C",0,0);

   /*  */
    $txt="<span>Imp Permit No :<b>".$GLOBALS['permitNo']."</b></span>";
    $pdf->WriteTag(78,0,$txt,0,"R",0,0);

    $pdf->Ln(7);
    $pdf->SetX(6);

    // Permit Date
    $txt="<span>Dated. <b>".$GLOBALS['permitDate']."</b></span>";
    $pdf->SetLineWidth(0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetDrawColor(0,0,0);
    $pdf->WriteTag(65,0,$txt,0,"L",0,0);

    $txt="<span>Date :<b>".$GLOBALS['tpDate']."</b></span>";
    $pdf->WriteTag(55,0,$txt,0,"C",0,0);

    $txt="<span>Valid Date. <b>".$GLOBALS['validDate']."</b></span>";
    $pdf->WriteTag(78,0,$txt,0,"R",0,0);

    //$pdf->SetX(5,45);

    $pdf->Line(5, 35, 5, 50);
    $pdf->Line(5, 50, 205, 50);
    $pdf->Line(205, 50, 205, 35);

    $pdf->SetXY(6,54);

    /* $partyName  =   "Valentine Bonded Warehouse";
    $pan    =   "adssdsaasdfdsf";
    $tin    =   "212121211212122"; */
    // Party Info

    $pdf->SetLineWidth(0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetDrawColor(0,0,0);

    $txt="<span>Party :<b>".$GLOBALS['partyName']."</b></span>";
    $pdf->WriteTag(100,0,$txt,0,"L",0,0);

    $txt="<span>PAN :<b>".$GLOBALS['partyPan']."</b></span>";
    $pdf->WriteTag(50,0,$txt,0,"L",0,0);

    $txt="<span>ECN :<b></b></span>";
    $pdf->WriteTag(48,0,$txt,0,"R",0,0);

    $pdf->Line(5, 50, 5, 58);
    $pdf->Line(5, 58, 205, 58);
    $pdf->Line(205, 58, 205, 50);

}

function DataTableHeaderOnly($pdf){
    $pdf->SetFillColor(253,251,247);
    $header = array('S No', 'Description of Goods', 'Alt. Qty', 'Qty', 'Rate', 'Per', 'Amount');
    $pdf->SetFont('ANC', 'B', 10);
    // Column widths
    $w = array(8, 100, 18, 16, 22, 11, 25);
    // Header
    for($i=0;$i<count($header);$i++)
        $pdf->Cell($w[$i],7,$header[$i],1,0,'C',true);
}

$ALT_QTY_SUM=0;
$QTY_SUM=0;

function DataTable($header, $data, $ledger, $pdf)
{
    $headerY    =   58;
    $headerLessY  =   10;
    $footStartY =   212;
    $bottomY =   278;

    $pdf->SetFont('ANC', 'B', 10);
    // Column widths
    $w = array(8, 100, 18, 16, 22, 11, 25);
    // Header
    $pdf->SetFillColor(253,251,247);
    for($i=0;$i<count($header);$i++)
        $pdf->Cell($w[$i],7,$header[$i],1,0,'C',true);

    $pdf->Ln();
    $pdf->SetXY(5,66);
    // Data

    $pdf->SetFont('ANC', '', 10);

    $LIMIT_ROWS   =   34;
    $MAX_ROWS_PER_PAGE   =   40;
    $TOTAL_ROWS  =   count($data)+count($ledger);
    $CURRENT_ROW    =   1;

    $PAGES  =   $TOTAL_ROWS % $MAX_ROWS_PER_PAGE;
    if(($TOTAL_ROWS % $MAX_ROWS_PER_PAGE)>0 ){
        $PAGES  +=1;
    }
    
    //$LastYCursor=0;

    $RUNNING_TOTAL=0;
    $IPF_ADV_TOTAL=0;
    $TCS_TOTAL=0;

    for($i=0;$i<count($data);$i++)
    {  
        if($GLOBALS['FOOTER_ALL_PAGES'])
        {
            if($CURRENT_ROW > $LIMIT_ROWS){

                CustomFooter($pdf);

                //PRINT VERTICAL LINES FROM TOP TO FOOTER
                $xCoord =   5;
                PrintVerticalLinesFull($pdf,$header,$w,$xCoord,$headerY+6,$footStartY);
        
                $pdf->AddPage();
                $pdf->SetX(5);

                //Check Header Enable or Not
                if($GLOBALS['HEADER_ALL_PAGES']){
                    CustomHeader($pdf);
                }
                else{
                    $headerY = $headerLessY;
                    $LIMIT_ROWS+=8;
                }
                
                $pdf->SetXY(5,$headerY);
    
                DataTableHeaderOnly($pdf);
                $pdf->SetFont('ANC', '', 10);
                $pdf->Ln();
                $pdf->SetX(5);
                $CURRENT_ROW=1;
            }
        }
        else{

            if(($CURRENT_ROW >= $LIMIT_ROWS) && ($CURRENT_ROW < $MAX_ROWS_PER_PAGE)){
                //PRINT VERTICAL LINES FROM TOP TO FOOTER
                PrintVerticalLinesFull($pdf,$header,$w,5,$headerY+6,$bottomY);
    
                //Print Bottom Line
                $pdf->Line(5, $bottomY, 205, $bottomY);
            }
    
            if($CURRENT_ROW == $MAX_ROWS_PER_PAGE){
                //PRINT VERTICAL LINES FROM TOP TO FOOTER
                PrintVerticalLinesFull($pdf,$header,$w,5,$headerY+6,$bottomY);
    
                //Print Bottom Line
                $pdf->Line(5, $bottomY, 205, $bottomY);
            }
    
            if($CURRENT_ROW > $MAX_ROWS_PER_PAGE){
    
                //PRINT VERTICAL LINES FROM TOP TO FOOTER
                PrintVerticalLinesFull($pdf,$header,$w,5,$headerY+6,$bottomY);
                
                //Print Bottom Line
                $pdf->Line(5, $bottomY, 205, $bottomY);
    
                $pdf->AddPage();
                $pdf->SetX(5);
    
                //Check Header Enable or Not
                if($GLOBALS['HEADER_ALL_PAGES']){
                    CustomHeader($pdf);
                }
                else
                {
                    $headerY = $headerLessY;
                    $MAX_ROWS_PER_PAGE +=8;
                }
    
                $pdf->SetXY(5,$headerY);
                
                DataTableHeaderOnly($pdf);
                $pdf->SetFont('ANC', '', 10);
                $pdf->Ln();
                $pdf->SetX(5);
                $CURRENT_ROW=0;
            }
        }

        
        
        $altQty =   intVal($data[$i][2]);

        if (intVal($data[$i][3]) > 0){$qtyincase = intVal($data[$i][3]);}else{$qtyincase = number_format($data[$i][2]/$data[$i][7],2);}

        $pdf->Cell($w[0],5,$i+1,'');
        $pdf->Cell($w[1],5,ucfirst(strtolower($data[$i][1])),'');
        /*$pdf->Cell($w[2],6,$altQty." Btls",'',0,'R');
        $pdf->Cell($w[3],6,intVal($data[$i][3])." Case",'',0,'R');*/
        $pdf->Cell($w[2],5,$altQty." B",'',0,'R');
        //$pdf->Cell($w[3],5,intVal($data[$i][3])." C",'',0,'R');
        $pdf->Cell($w[3],5,$qtyincase." C",'',0,'R');
        $pdf->Cell($w[4],5,$data[$i][4],'',0,'R');
        //$pdf->Cell($w[5],5,$data[$i][5],'',0);
        $pdf->Cell($w[5],5,'CASE','',0);
        $pdf->Cell($w[6],5,$data[$i][6],'',0,'R');

        $RUNNING_TOTAL += $data[$i][6];

        $LastYCursor=$pdf->GetY();

        $pdf->Ln();
        $pdf->SetX(5);

        $GLOBALS['ALT_QTY_SUM'] += $altQty;
        $GLOBALS['QTY_SUM'] += $data[$i][3];
        $CURRENT_ROW++;
        
    }

    //Display Item Total Price
    
    $pdf->SetX(180);
    $LastYCursor=$pdf->GetY();
    //$LastXCursor=$pdf->GetX();
    $pdf->Line(180, $LastYCursor, 205, $LastYCursor);

   
    $pdf->SetX(195);
    $pdf->Cell(10,6,$RUNNING_TOTAL,'',0,'R');
    $CURRENT_ROW++;

    $pdf->Ln();
    $pdf->SetX(5);

    for($i=0;$i<count($ledger);$i++)
    {

        if($GLOBALS['FOOTER_ALL_PAGES'])
        {
            if($CURRENT_ROW > $LIMIT_ROWS){

                CustomFooter($pdf);
                //PRINT VERTICAL LINES FROM TOP TO FOOTER
                $xCoord =   5;
                PrintVerticalLinesFull($pdf,$header,$w,$xCoord,$headerY+6,$footStartY);
        
                $pdf->AddPage();
                $pdf->SetX(5);

                //Check Header Enable or Not
                if($GLOBALS['HEADER_ALL_PAGES']){
                    CustomHeader($pdf);
                    
                }
                else{
                    $headerY = $headerLessY;
                    $LIMIT_ROWS+=8;
                }
                
                $pdf->SetXY(5,$headerY);
    
                DataTableHeaderOnly($pdf);
                $pdf->SetFont('ANC', '', 10);
                $pdf->Ln();
                $pdf->SetX(5);
                $CURRENT_ROW=1;
            }
        }
        else
        {
            if(($CURRENT_ROW >= $LIMIT_ROWS) && ($CURRENT_ROW < $MAX_ROWS_PER_PAGE)){
                //PRINT VERTICAL LINES FROM TOP TO FOOTER
                PrintVerticalLinesFull($pdf,$header,$w,5,$headerY+6,$bottomY);
    
                //Print Bottom Line
                $pdf->Line(5, $bottomY, 205, $bottomY);
            }
    
            if($CURRENT_ROW == $MAX_ROWS_PER_PAGE){
                //PRINT VERTICAL LINES FROM TOP TO FOOTER
                PrintVerticalLinesFull($pdf,$header,$w,5,$headerY+6,$bottomY);
    
                //Print Bottom Line
                $pdf->Line(5, $bottomY, 205, $bottomY);
            }
    
            if($CURRENT_ROW > $MAX_ROWS_PER_PAGE){
    
                //PRINT VERTICAL LINES FROM TOP TO FOOTER
                PrintVerticalLinesFull($pdf,$header,$w,5,$headerY+6,$bottomY);
                
                //Print Bottom Line
                $pdf->Line(5, $bottomY, 205, $bottomY); 
    
                $pdf->AddPage();
                $pdf->SetX(5);
    
                //Check Header Enable or Not
                if($GLOBALS['HEADER_ALL_PAGES']){
                    CustomHeader($pdf);
                }
                else{
                    $headerY = $headerLessY;
                    $MAX_ROWS_PER_PAGE +=8;
                }
                
                $pdf->SetXY(5,$headerY);
    
                DataTableHeaderOnly($pdf);
                $pdf->SetFont('ANC', '', 10);
                $pdf->Ln();
                $pdf->SetX(5);
                $CURRENT_ROW=0;
            }
        }

        //total of first 2 ledgers
        if($i==2){
            $IPF_ADV_TOTAL = $ledger[0][3] + $ledger[1][3] + $RUNNING_TOTAL;
            $pdf->SetX(180);
            $LastYCursor=$pdf->GetY();
            $pdf->Line(180, $LastYCursor, 205, $LastYCursor);
            $pdf->SetX(195);
            $pdf->Cell(10,6,$IPF_ADV_TOTAL,'',0,'R');
            $CURRENT_ROW++;

            $pdf->Ln();
            $pdf->SetX(5);
        }

        

        /* start - to check and hold the roff ledger manajit - 14042025 */
        if($ledger[$i][0] == "Round-off"){
            $roffval = $ledger[$i][3];
        }
        /* end - to check and hold the roff ledger manajit - 14042025 */
        /*if ($GLOBALS['includevatandlf'] == 'no'){
            if($ledger[$i][0] != "Round-off"){
                $pdf->Cell($w[0],6,'','');
                $pdf->Cell($w[1],6,$ledger[$i][1],'',0,'R');
                $pdf->Cell($w[2],6,'','',0);
                $pdf->Cell($w[3],6,'','',0);
                $pdf->Cell($w[4],6,'','');
                $pdf->Cell($w[5],6,'','',0);
                $pdf->Cell($w[6],6,$ledger[$i][3],'',0,'R');
            }
        }elseif ($GLOBALS['includevatandlf'] == 'yes'){*/

            $pdf->Cell($w[0],6,'','');
            $pdf->Cell($w[1],6,$ledger[$i][1],'',0,'R');
            $pdf->Cell($w[2],6,'','',0);
            $pdf->Cell($w[3],6,'','',0);
            $pdf->Cell($w[4],6,'','');
            $pdf->Cell($w[5],6,'','',0);
            $pdf->Cell($w[6],6,$ledger[$i][3],'',0,'R');
        //}
        //$LastYCursor=$pdf->GetY();

        $pdf->Ln();
        $pdf->SetX(5);

        $CURRENT_ROW++;

        //total of first 2 ledgers + tcs + running total
        if($i==2){
            $TCS_TOTAL = $IPF_ADV_TOTAL + $ledger[2][3];
            $pdf->SetX(180);
            $LastYCursor=$pdf->GetY();
            $pdf->Line(180, $LastYCursor, 205, $LastYCursor);
            $pdf->SetX(195);
            $pdf->Cell(10,6,$TCS_TOTAL,'',0,'R');
            $CURRENT_ROW++;

            $pdf->Ln();
            $pdf->SetX(5);
        }
    }

    //Checks Last Page
    if($GLOBALS['FOOTER_ALL_PAGES']){
        CustomFooter($pdf);

        //PRINT VERTICAL LINES FROM TOP TO FOOTER
        $xCoord =   5;
        PrintVerticalLinesFull($pdf,$header,$w,$xCoord,$headerY+6,$footStartY);
    }
    else{
        
        if($CURRENT_ROW > $LIMIT_ROWS){
        
            $pdf->AddPage();
            $pdf->SetX(5);

            //Check Header Enable or Not
            if($GLOBALS['HEADER_ALL_PAGES']){
                CustomHeader($pdf);
            }else{
                $headerY = $headerLessY;
            }

            $pdf->SetXY(5,$headerY);

            DataTableHeaderOnly($pdf);
            CustomFooter($pdf);
            
        }else{
            CustomFooter($pdf);
        }

        $pdf->Ln();
        //$pdf->SetXY(5,65);


        //PRINT VERTICAL LINES FROM TOP TO FOOTER
        $xCoord =   5;
        PrintVerticalLinesFull($pdf,$header,$w,$xCoord,$headerY+6,$footStartY);
    }
    
}

function PrintVerticalLinesFull($pdf,$header,$cellWidth,$x1,$y1,$y2){
    //$x =   5;
    $pdf->Line($x1, $y1, $x1, $y2);
    //$pdf->SetLineWidth(0.15);

    for($j=0;$j<count($header);$j++)
    {
        $x1 =   $x1+$cellWidth[$j];
        $pdf->Line($x1, $y1, $x1, $y2);
    }
}

//Custom Footer
function CustomFooter($pdf){
    $footStartY =   212;

    $pdf->SetXY(5,$footStartY);

    $extendY=7;

    $pdf->Line(5, $footStartY, 205, $footStartY);
    $pdf->Line(5, $footStartY, 5, $footStartY+$extendY);
    $pdf->Line(5, $footStartY+$extendY, 205, $footStartY+$extendY);
    $pdf->Line(205, $footStartY+$extendY, 205, $footStartY);

    //footer total vertical lines
    $pdf->Line(113, $footStartY, 113, 280);
    $pdf->Line(131, $footStartY, 131, $footStartY+$extendY);
    $pdf->Line(147, $footStartY, 147, $footStartY+$extendY);
    $pdf->Line(169, $footStartY, 169, $footStartY+$extendY);
    $pdf->Line(180, $footStartY, 180, $footStartY+$extendY);

    //$pdf->Line(5, $footStartY, 205, $footStartY);
    //$pdf->Line(5, $footStartY, 5, $footStartY+$extendY);
    //$pdf->Line(5, 226, 205, 226);

    //footer box
    $pdf->Line(5, 217, 5, 280);
    $pdf->Line(5, 280, 205, 280);
    $pdf->Line(205, 280, 205, 217);


    // Total + Bottles + Cases + Amount 
    $pdf->SetXY(5,215);
    $pdf->SetFont('ANC', '', 11);
    $pdf->Cell(108, 0, "Total", 0, 0, 'R');
    //$pdf->Cell(18, 0, $GLOBALS['ALT_QTY_SUM']." Btls", 0, 0, 'C');
    $pdf->SetFont('ANC', '', 9);
    $pdf->Cell(18, 0, $GLOBALS['ALT_QTY_SUM']." B", 0, 0, 'C');
    //$pdf->Cell(16, 0, $GLOBALS['QTY_SUM']." Case", 0, 0, 'C');
    $pdf->Cell(16, 0, $GLOBALS['QTY_SUM']." C", 0, 0, 'C');
    $pdf->SetFont('ANC', '', 10);
    $pdf->Cell(22, 0, "", 0, 0, 'R');
    $pdf->Cell(11, 0, "", 0, 0, 'R');

    /* edited by manajit on 03042025 to check and include vat and lf in grand total or not */
    if ($GLOBALS['includevatandlf'] == 'no'){
        //$new_grandtotal =  $GLOBALS['grandTotal'] - ($lfval + $vatval); 
        $pdf->Cell(25, 0, floatval(floor($GLOBALS['new_grandtotal'])) . ".00", 0, 0, 'R');  
        $amtInWords =   numberTowords($GLOBALS['new_grandtotal']);
    }else{
        $pdf->Cell(25, 0, $GLOBALS['grandTotal'], 0, 0, 'R');
        $amtInWords =   numberTowords($GLOBALS['grandTotal']);
    }
    //echo "new grandtotal ".$GLOBALS['new_grandtotal']."<br>";
    //$pdf->Cell(25, 0, $GLOBALS['grandTotal'], 0, 0, 'R');
    /* edited by manajit on 03042025 to check and include vat and lf in grand total or not */

    //Invoice amount in words + Company Details + Terms & Conditions
    $pdf->SetXY(7,222);

    $pdf->SetStyle("span","ANC","N",11,"0,0,0",0);
    $pdf->SetStyle("b","ANC","B",11,"0,0,0");

    $pdf->SetLineWidth(0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetDrawColor(0,0,0);

    $txt="<span>Nett invoice amount in words</span>";
    $pdf->WriteTag(100,0,$txt,0,"L",0,0);

    $txt="<span>for,<b> ".$GLOBALS['companyName']."</b></span>";
    $pdf->WriteTag(97,0,$txt,0,"R",0,0);

    $cursorY    =   227;

    $pdf->SetXY(7,$cursorY);

    /* $amtInWords =   numberTowords($GLOBALS['grandTotal']); */
    $amtInWords="(".$amtInWords.")";

    $amtArray   =   str_split($amtInWords,60);
    $amtDisplay =   "";

    for ($i=0; $i < count($amtArray); $i++) { 
        //$amtDisplay.=$amtArray[$i]."<br>";
        $pdf->Cell(105,0,$amtArray[$i],0,0,'L');
        $pdf->Ln(5);
        $pdf->SetX(7);

        
    }

    $cursorY +=7;

    $pdf->SetXY(7,$cursorY);

    $pdf->SetFont('ANC', '', 11);

    /* $pdf->Cell(50,0,"VAT Total:",0,0,'L');
    $pdf->Cell(50,0,"Lifting Fee:",0,0,'L');

    $cursorY+=4;
 
    $pdf->SetXY(7,$cursorY);
    $pdf->Cell(50,0,"Narration:",0,0,'L');
*/
    $cursorY+=3.5;

    // start - edited by manajit - 21122023 - to display vat and lf as info value
    $vatndlf_str = "VAT : ".$GLOBALS['vatval']." Lifting Fee : ".$GLOBALS['lfval'];
    $pdf->SetXY(7,$cursorY);
    $pdf->Cell(50,0,$vatndlf_str,0,0,'L');
    // end - edited by manajit - 21122023 - to display vat and lf as info value

    $cursorY+=4.5;

    $pdf->SetXY(7,$cursorY);
    $pdf->Cell(50,0,"Company's Bank Details:",0,0,'L');

    $cursorY+=4;

    $pdf->SetXY(9,$cursorY);
    $pdf->Cell(50,0,"Beneficiary Name: ".$GLOBALS['companyBeneName'],0,0,'L');

    $cursorY+=4;

    $pdf->SetXY(9,$cursorY);
    $pdf->Cell(50,0,"Bank Name: ".$GLOBALS['companyBankName'],0,0,'L');

    $cursorY+=4;

    $pdf->SetXY(9,$cursorY);
    $pdf->Cell(50,0,"A/c No: ".$GLOBALS['bankAccNo'],0,0,'L');

    $cursorY+=4;

    $pdf->SetXY(9,$cursorY);
    $pdf->Cell(50,0,"Branch & IFS Code: ".$GLOBALS['bankBrName']." , ".$GLOBALS['bankIFSC'],0,0,'L');

    $cursorY+=4;

    $pdf->SetXY(7,$cursorY);
    /* $pdf->Cell(50,0,"Terms & Conditions",0,0,'L'); */

    $pdf->SetFont('ANC', 'B', 9);

    $cursorY+=4;

    $pdf->SetXY(7,$cursorY);
    $pdf->Cell(50,0,"1) No GST has been charged in this invoice since supply of ",0,0,'L');

    $cursorY+=3;
    $pdf->SetXY(7,$cursorY);
    $pdf->Cell(50,0," ALCOHOLIC BEVERAGES FOR HUMAN CONSUMPTION is outside",0,0,'L');

    $cursorY+=3;
    $pdf->SetXY(7,$cursorY);
    $pdf->Cell(50,0," the purview of GST.",0,0,'L');

    $cursorY+=3;

    $pdf->SetXY(7,$cursorY);
    $pdf->Cell(50,0,"2) Payment by cheque subject to realisation.",0,0,'L');

    $cursorY+=3;

    /* $pdf->SetXY(7,$cursorY);
    $pdf->Cell(50,0,"3.Payment by cheque subject to realisation",0,0,'L');

    $cursorY+=3;

    $pdf->SetXY(7,$cursorY);
    $pdf->Cell(50,0,"4.VAT and Lifting Fee paid directly to State Govt. by retailers",0,0,'L'); */

    $pdf->SetFont('ANC', '', 11);
    $pdf->Cell(147,0,"Authorised Signatory",0,0,'R');


    /* if(strlen($amtInWords)>50){
        
    } */

    //$txt="<span><b>($amtDisplay)</b></span>";
    //$pdf->WriteTag(100,0,$txt,0,"L",0,0);
}

//Number To Words
function numberTowords($number) {
    $no = round($number);
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
      0 => '',
      1 => 'One',
      2 => 'Two',
      3 => 'Three',
      4 => 'Four',
      5 => 'Five',
      6 => 'Six',
      7 => 'Seven',
      8 => 'Eight',
      9 => 'Nine',
      10 => 'Ten',
      11 => 'Eleven',
      12 => 'Twelve',
      13 => 'Thirteen',
      14 => 'Fourteen',
      15 => 'Fifteen',
      16 => 'Sixteen',
      17 => 'Seventeen',
      18 => 'Eighteen',
      19 => 'Nineteen',
      20 => 'Twenty',
      30 => 'Thirty',
      40 => 'Forty',
      50 => 'Fifty',
      60 => 'Sixty',
      70 => 'Seventy',
      80 => 'Eighty',
      90 => 'Ninety');
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_length) {
      $divider = ($i == 2) ? 10 : 100;
      $number = floor($no % $divider);
      $no = floor($no / $divider);
      $i += $divider == 10 ? 1 : 2;
      if ($number) {
        $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
        $str [] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural;
      } else {
        $str [] = null;
      }
    }

    $Rupees = implode(' ', array_reverse($str));
    $paise = ($decimal) ? "And Paise " . ($words[$decimal - $decimal%10]) ." " .($words[$decimal%10])  : '';
    return ($Rupees ? 'Rupees ' . $Rupees : '') . $paise . " Only";
}



//Fetch Print Settings
$query_print_settings = "SELECT sl_no,settings_name,settings_action,settings_type FROM tbl_print_settings";
$printSettings  =   GetData($query_print_settings,$dbh);

for($i=0;$i<count($printSettings);$i++){
    
    //Header On All Pages
    if($printSettings[$i][0]=="1"){
        if($printSettings[$i][2]=="0"){
            $HEADER_ALL_PAGES   =   false;
        }else{
            $HEADER_ALL_PAGES   =   true;
        }
    }

    //Footer On All Pages
    if($printSettings[$i][0]=="2"){
        if($printSettings[$i][2]=="0"){
            $FOOTER_ALL_PAGES   =   false;
        }else{
            $FOOTER_ALL_PAGES   =   true;
        }
    }

    //Footer Text
    if($printSettings[$i][0]=="3"){
        $footerText =  $printSettings[$i][1] ;
    }

}


//$pdf->SetFooterText("Hello");

//$FOOTER_ALL_PAGES   =   false;
$tableStartY =   58;


  // Instanciation of inherited class
$pdf = new PDF_WriteTag($footerText);

$pdf->AddFont('ANC','','ArialNovaCond.php');
$pdf->AddFont('ANC','B','ArialNovaCond-Bold.php');
// Instanciation of inherited class

$pdf->AliasNbPages();
$pdf->AddPage();
//$pdf->SetFont('Times','',12);
$pdf->SetFont('ANC','B',12);

//$pdf->SetFillColor(224,235,255);



//Data
$vchNo = $_GET['vchno']; //echo $vchNo;

//Company Info
$query_get_company="SELECT sname,saddress,semailid,stinno,spanno FROM tbl_company";
$compData   =   GetData($query_get_company,$dbh);   //print_r($compData);

$companyName    =   $compData[0][0];
$companyAddress =   $compData[0][1];
$companyEmail   =   $compData[0][2];
$companyTin     =   $compData[0][3];
$companyPan     =   $compData[0][4];

//Company Bank Info
$query_get_companybank="SELECT beneficiarynm,banknm,accountno,branchnm,ifscode FROM tbl_bankdetails WHERE printbank=1;";
$compBankData   =   GetData($query_get_companybank,$dbh);   //print_r($compBankData);

$companyBeneName    =   $compBankData[0][0];    //echo $companyBeneName."<br>";
$companyBankName    =   $compBankData[0][1];    //echo $companyBankName."<br>";
$bankAccNo          =   $compBankData[0][2];    //echo $bankAccNo."<br>";
$bankBrName         =   $compBankData[0][3];    //echo $bankBrName."<br>";
$bankIFSC           =   $compBankData[0][4];    //echo $bankIFSC."<br>";

//Get TP Info
$query_get_tp="SELECT reftpno FROM tbl_vchtpordermap WHERE vchno='$vchNo'";
$tpData   =   GetData($query_get_tp,$dbh);
$tpNo   =   $tpData[0][0]; /*echo $tpNo;*/

$query_get_tp_date="SELECT DATE_FORMAT(reftpdate,'%d-%m-%Y') FROM tbl_vchtpordermap WHERE reftpno='$tpNo'";
$tpData   =   GetData($query_get_tp_date,$dbh);
$tpDate   =   $tpData[0][0]; /*echo $tpDate;*/

//Bill Info
$query_get_bill = "SELECT DATE_FORMAT(qm.vchdate,'%d-%m-%Y') AS vchdate,qm.vchtype,qm.grandtotal,qm.narration,'',pm.partyname,pm.partyadd,pm.partycity,pm.partystate,pm.partystatecode,pm.partypincode,pm.partymobileno,IF(pm.partyemailid='','NF',pm.partyemailid) partyemailid,IF(pm.partypan='','NF',pm.partypan) partypan,IFNULL(pm.partytin,'0') partytin,'',IFNULL(qm.importpermitno,0) importpermitno,IFNULL(DATE_FORMAT(qm.importpermitdate,'%d-%m-%Y'),'0000-00-00') importpermitdate,qm.narration,IFNULL(DATE_FORMAT(qm.supplierinvdate,'%d-%m-%Y'),'0000-00-00') validitydate FROM tbl_transactionmaster AS qm INNER JOIN tbl_partymaster AS pm ON qm.partycode=pm.partycode WHERE qm.vchno='$vchNo' AND stype='0'";
$billData   =   GetData($query_get_bill,$dbh); /*print_r($billData);*/ /*echo $query_get_bill;*/

$billDate  = $billData[0][0];
//$vchtype  = $billData[0][1];
$svchtype  = $billData[0][1];       //echo $svchtype;
$grandTotal  = $billData[0][2];
$narration  = $billData[0][3];
$vatRate  = $billData[0][4];
$partyName  = $billData[0][5];
/* $pAdd  = $billData[0][6];
$pCity  = $billData[0][7];
$pstate  = $billData[0][8];
$pstatecode  = $billData[0][9];
$ppincode  = $billData[0][10];
$pmobile  = $billData[0][11];
$pemail  = $billData[0][12]; */
$partyPan  = $billData[0][13];
$partyTin  = $billData[0][14];
//$vchmode  = $billData[0][15];
$permitNo  = $billData[0][16];
$permitDate  = $billData[0][17];
//$tpNo="";
$validDate= $billData[0][19];
//$narration  = $billData[0][18];

//Permit Info
$query_get_tpdtls = "SELECT qm.permitno,DATE_FORMAT(qm.permitdate,'%d-%m-%Y'),DATE_FORMAT(qm.permitvalidity,'%d-%m-%Y') FROM tbl_tpmaster AS qm WHERE qm.vchno='$tpNo'";
$tpdtlsData   =   GetData($query_get_tpdtls,$dbh); /*print_r($tpdtlsData);*/ /*echo $query_get_tpdtls;*/

$permitNo  = $tpdtlsData[0][0];
$permitDate  = $tpdtlsData[0][1];
$validDate= $tpdtlsData[0][2];

//Item Details
//echo "grpbyinsi ".$GLOBALS['grpbyinsi']."<br>";
if ($GLOBALS['grpbyinsi'] == 0){
$query_get_items="SELECT qid.itemcode,pm.itemname,ABS(qid.itemaltqty),ABS(qid.itemqty),qid.salerate,qid.uom,qid.itemamount,pm.altconv,qid.taxamt2,qid.taxamt1,'','','',pm.altuom FROM tbl_transactionmaster AS qm INNER JOIN tbl_transactionitemdesc AS qid ON qm.vchno=qid.vchno INNER JOIN tbl_productmasterstorewise AS pm ON  qid.itemcode=pm.itemcode WHERE qm.vchno='$vchNo' AND stype='0'";}
if ($GLOBALS['grpbyinsi'] == 1){
$query_get_items="SELECT qid.itemcode,pm.itemname,SUM(ABS(qid.itemaltqty)),SUM(ABS(qid.itemqty)),qid.salerate,qid.uom,qid.itemamount,pm.altconv,qid.taxamt2,qid.taxamt1,'','','',pm.altuom FROM tbl_transactionmaster AS qm INNER JOIN tbl_transactionitemdesc AS qid ON qm.vchno=qid.vchno INNER JOIN tbl_productmasterstorewise AS pm ON  qid.itemcode=pm.itemcode WHERE qm.vchno='$vchNo' AND stype='0' GROUP BY qid.itemcode";}
//echo $query_get_items;
$itemData = GetData($query_get_items,$dbh);

//Ledger Details
$hideledger = "";
$hidecalculate = "";
//echo $GLOBALS['showVATledger']."<br>";
//echo $GLOBALS['showLFledger']."<br>";

if ($GLOBALS['showVATledger'] == 1 && $GLOBALS['showLFledger'] == 1){
    $hideledger = "";
    $hidecalculate = "";
    $isVATandLFincluded = true;
}elseif ($GLOBALS['showVATledger'] == 0 && $GLOBALS['showLFledger'] == 0){
    $hideledger = "'VAT','LF'";
    $hidecalculate = "'VAT','LF'";
    $isVATandLFincluded = false;
}elseif ($GLOBALS['showVATledger'] == 0 && $GLOBALS['showLFledger'] == 1){
    $hideledger = "'VAT'";
    $hidecalculate = "'VAT'";
    $isVATandLFincluded = false;
}elseif ($GLOBALS['showVATledger'] == 1 && $GLOBALS['showLFledger'] == 0){
    $hideledger = "'LF'";
    $hidecalculate = "'LF'";
    $isVATandLFincluded = false;
}else{
    $hideledger = "";
    $hidecalculate = "";
    $isVATandLFincluded = true;
}
if (strlen($hideledger) > 0){
    $hideledger = " AND qld.ledname NOT IN (".$hideledger.")";
    $hidecalculate = " AND ledname NOT IN (".$hidecalculate.")";
    //$roundOffVal = calculateRoundoff();
}
//$query_get_ledger="SELECT qld.ledname,lm.displayledgername AS ledger_print_name,qld.ledrate,qld.ledamount,lm.calcType FROM tbl_transactionledgerdesc AS qld INNER JOIN tbl_ledgermaster AS lm ON qld.ledname=lm.ledgername WHERE qld.vchno='$vchNo' AND lm.forvchtype='$svchtype' AND qld.ledname NOT IN ('VAT','LF') AND qld.ledamount > 0 ORDER BY lm.ledgerposition";
$query_get_ledger="SELECT qld.ledname,lm.displayledgername AS ledger_print_name,qld.ledrate,qld.ledamount,lm.calcType FROM tbl_transactionledgerdesc AS qld INNER JOIN tbl_ledgermaster AS lm ON qld.ledname=lm.ledgername WHERE qld.vchno='$vchNo' AND lm.forvchtype='$svchtype'".$hideledger." ORDER BY lm.ledgerposition";
$ledgerData = GetData($query_get_ledger,$dbh); /*echo $query_get_ledger; print_r($ledgerData);*/

//only vat and lf Details
$query_get_vatndlfledger="SELECT ledname,ledamount FROM tbl_transactionledgerdesc WHERE vchno='$vchNo' AND ledname IN ('VAT','LF')";
$vatndlfledgerData = GetData($query_get_vatndlfledger,$dbh); /*echo $query_get_vatndlfledger; print_r($vatndlfledgerData);*/
$lfval = $vatndlfledgerData[0][1]; //echo $lfval."<br>";
$vatval = $vatndlfledgerData[1][1]; //echo $vatval;

//echo "roffval ".$GLOBALS['roffval'];
    /* edited by manajit on 03042025 to check and include vat and lf in grand total or not */
    if ($GLOBALS['includevatandlf'] == 'no'){
        $new_grandtotal = $GLOBALS['grandTotal'] ; 
        $roundOffVal = calculateRoundoff($new_grandtotal); //echo $roundOffVal;
    }else{
        $new_grandtotal =  $GLOBALS['grandTotal'];
    }
    /* edited by manajit on 03042025 to check and include vat and lf in grand total or not */

//Header
CustomHeader($pdf);

//Body
$pdf->Ln();
$pdf->SetXY(5,58);



$header = array('S No', 'Description of Goods', 'Alt. Qty', 'Qty', 'Rate', 'Per', 'Amount');

DataTable($header, $itemData, $ledgerData, $pdf);

//$pdf->Output();
$pdf->Output('temp/saleinvoice.pdf','F');
echo 'temp/saleinvoice.pdf';
?>