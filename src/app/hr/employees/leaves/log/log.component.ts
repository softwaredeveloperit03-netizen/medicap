import { Component, OnInit } from '@angular/core';
import jsPDF from 'jspdf';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  selectedResult;
  list;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getData();
   
  }
    getData() {    
      this.service.get('hr/leavepolicy.php?type=getleave').subscribe(response => {
        this.results = response;
      
      });
    }

    view(index) {
      this.selectedResult = this.results[index];
      // this.list = this.selectedResult['leaveList'];
      this.isView = true;
    }
    generatePDF() {
      const contentToConvert = document.getElementById('contentToConvert'); //--Get the element
      this.service.convertToPDF(contentToConvert,'Po-Download');
    }

    // generateNewPDF(): void {
    //   const doc = new jsPDF();
    //   let startY = 20;
    //   const pageWidth = doc.internal.pageSize.getWidth();
    //   const pageHeight = doc.internal.pageSize.getHeight();
  
    //   const headers = ['Sr No.', 'Employee Name', 'Employee Id', 'Total Leaves', 'Leave Taken', 'Balance Leave'];
    //   const columns = this.results.map((user, index) => [
    //     (index + 1).toString(),
    //     `${user.afirstname} ${user.alastname}`,
    //     user.emp_id ? user.emp_id.toString() : '',
    //     user.total_leave != null ? user.total_leave.toString() : '',
    //     user.leave_taken != null ? user.leave_taken.toString() : '',
    //     user.leave_balance != null ? user.leave_balance.toString() : ''
    //   ]);
  
    //   // Add Title
    //   doc.setFontSize(14);
    //   doc.text('Employee Leave Report', pageWidth / 2, startY, { align: 'center' });
    //   startY += 10;
  
    //   // Add Table Headers
    //   doc.setFontSize(10);
    //   doc.setFillColor(14, 67, 112);
    //   doc.setTextColor(255, 255, 255);
    //   doc.rect(10, startY, pageWidth - 20, 10, 'F');
    //   headers.forEach((header, index) => {
    //     const xPos = 12 + (index * (pageWidth - 20) / headers.length);
    //     doc.text(header, xPos, startY + 7);
    //   });
    //   startY += 10;
  
    //   // Add Table Rows
    //   doc.setTextColor(0, 0, 0);
    //   columns.forEach((row, rowIndex) => {
    //     row.forEach((cell, cellIndex) => {
    //       const xPos = 12 + (cellIndex * (pageWidth - 20) / headers.length);
    //       doc.text(cell, xPos, startY + 7);
    //     });
    //     startY += 10;
  
    //     // Check if we need to add a new page
    //     if (startY > pageHeight - 20) {
    //       doc.addPage();
    //       startY = 20;
    //     }
    //   });
  
    //   doc.save('EmployeeLeaveReport.pdf');
    // }

    
    generateNewPDF(): void {
      const doc = new jsPDF();
      let startY = 20;
      const pageWidth = doc.internal.pageSize.getWidth();
      const pageHeight = doc.internal.pageSize.getHeight();
    
      const headers = ['Sr No.', 'Employee Name', 'Employee Id', 'Total Leaves', 'Leave Taken', 'Balance Leave'];
      const columnWidths = [20, 50, 30, 30, 30, 30]; // Set desired widths for each column
    
      const columns = this.results.map((user, index) => [
        (index + 1).toString(),
        `${user.afirstname} ${user.alastname}`,
        user.emp_id ? user.emp_id.toString() : '',
        user.total_leave != null ? user.total_leave.toString() : '',
        user.leave_taken != null ? user.leave_taken.toString() : '',
        user.leave_balance != null ? user.leave_balance.toString() : ''
      ]);
    
      // Add Title
      doc.setFontSize(14);
      doc.text('Employee Leave Report', pageWidth / 2, startY, { align: 'center' });
      startY += 10;
    
      // Add Table Headers
      doc.setFontSize(10);
      doc.setFillColor(14, 67, 112);
      doc.setTextColor(255, 255, 255);
      doc.rect(10, startY, pageWidth - 20, 10, 'F');
    
      let currentX = 10;
      headers.forEach((header, index) => {
        doc.text(header, currentX + 2, startY + 7); // Adjust text position slightly
        doc.rect(currentX, startY, columnWidths[index], 10); // Draw header cell border
        currentX += columnWidths[index];
      });
      startY += 10;
    
      // Add Table Rows
      doc.setTextColor(0, 0, 0);
      columns.forEach((row) => {
        currentX = 10;
        row.forEach((cell, cellIndex) => {
          doc.text(cell, currentX + 2, startY + 7); // Adjust text position slightly
          doc.rect(currentX, startY, columnWidths[cellIndex], 10); // Draw cell border
          currentX += columnWidths[cellIndex];
        });
        startY += 10;
    
        // Check if we need to add a new page
        if (startY > pageHeight - 20) {
          doc.addPage();
          startY = 20;
        }
      });
    
      doc.save('EmployeeLeaveReport.pdf');
    }
    
  }
