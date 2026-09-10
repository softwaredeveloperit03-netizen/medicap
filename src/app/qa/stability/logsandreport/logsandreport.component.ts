import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import {ElementRef, ViewChild } from '@angular/core';

@Component({
  selector: 'app-logsandreport',
  templateUrl: './logsandreport.component.html',
  styleUrls: ['./logsandreport.component.css'],
})
export class LogsandreportComponent implements OnInit {
  report: any;
  @ViewChild('contentToConvert') contentToConvert: ElementRef;

  // Define strings for various stability reports
  stabilitySampleInwardRegForColdStorage = 'Stability Sample Inward Register For Cold Storage (Cold Storage 5±3°C)';
  stabilitySampleInwardReg = 'Stability Sample Inward Register (25°C)';
  stabilitySampleInwardRegister = 'Stability Sample Inward Register (30°C)';
  stabilitySummaryData = 'Stability Data Sheet For Accelerated Study';
  stabilitySummaryData1 = 'Stability Data Sheet For Real Time/Intermediate Study';
  sampleLocationChart = 'Sample Location Chart';
  stabilitySampleAnalysisReg = 'Stability Sample Analysis Register';
  monthlyPlanner = 'Monthly Planner For Stability Sample Withdrawal';
  annualPlanner = 'Annual Planner For Stability Study';
  stabilitySampleInWith = 'Stability Sample Inward/Withdrawal Register';

  constructor(
    private service: DataAccessService,
    private router: Router
  ) {}

  ngOnInit(): void {
  }

  closeAllView() {
  }


  download1(){
       this.service.open('qa/stability.php?type=QA_stability_pdf');

  }

  // download() {
  //   this.service.open('qa/product.php?type=QA_stability_pdf');
  // }



  
  // generateNewPDF(): void {
  //   const doc = new jsPDF('landscape'); // Set the PDF to landscape mode
  //   let startY = 20;
  //   const pageWidth = doc.internal.pageSize.getWidth();
  //   const pageHeight = doc.internal.pageSize.getHeight();
  
  //   const headers = [
  //     'Station (Month)', 'Batch no.', 'Scheduled Withdrawal date', 
  //     'Withdrawn on', 'Qty Withdrawn', 'Remaining Qty', 
  //     'Withdrawn by', 'Date of Report', 'Remark(if any)'
  //   ];
  //   const columnWidths = [30, 30, 40, 30, 30, 30, 30, 30, 30]; // Set desired widths for each column
  
  //   const rows = [
  //     ["", "", "", "", "", "", "", "", ""],
  //     ["", "", "", "", "", "", "", "", ""],
  //     ["", "", "", "", "", "", "", "", ""]
  //   ];
  
  //   // Add Title
  //   doc.setFontSize(14);
  //   doc.text('Storage Condition: 30°C/75 % RH [INTERMEDIATE / LONG TERM]', pageWidth / 2, startY, { align: 'center' });
  //   startY += 10;
  
  //   // Add Table Headers
  //   doc.setFontSize(10);
  //   doc.setFillColor(14, 67, 112);
  //   doc.setTextColor(255, 255, 255);
  //   doc.rect(10, startY, pageWidth - 20, 10, 'F');
  
  //   let currentX = 10;
  //   headers.forEach((header, index) => {
  //     doc.text(header, currentX + 2, startY + 7); // Adjust text position slightly
  //     doc.rect(currentX, startY, columnWidths[index], 10); // Draw header cell border
  //     currentX += columnWidths[index];
  //   });
  //   startY += 10;
  
  //   // Add Table Rows
  //   doc.setTextColor(0, 0, 0);
  //   rows.forEach((row) => {
  //     currentX = 10;
  //     row.forEach((cell, cellIndex) => {
  //       doc.text(cell, currentX + 2, startY + 7); // Adjust text position slightly
  //       doc.rect(currentX, startY, columnWidths[cellIndex], 10); // Draw cell border
  //       currentX += columnWidths[cellIndex];
  //     });
  //     startY += 10;
  
  //     // Check if we need to add a new page
  //     if (startY > pageHeight - 20) {
  //       doc.addPage();
  //       startY = 20;
  //     }
  //   });
  
  //   // Add "No Records Found!" row
  //   doc.text('No Records Found!', 12, startY + 7);
  //   doc.rect(10, startY, pageWidth - 20, 10); // Draw border around the row
  
  //   doc.save('StorageConditionReport.pdf');
  // }
  
  
}
