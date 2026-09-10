import { Component, OnInit } from '@angular/core';
import jsPDF from 'jspdf';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  loading;
  results;
  departments;
  selectedResult = [];
  isView = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDepartments();
    
    this.getInprocessMeetings();
  }

  getInprocessMeetings(){
    this.service.get('management/meeting.php?type=getMeetingslog').subscribe(response => {
      this.results = response;
    });
  }

  getDepartments() {
    this.service.get('common.php?type=getDepartments&department_name').subscribe(response => {
      this.departments = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  downloadLog(){
    this.service.open('management/meeting.php?type=MeetingslogPDF');
  }

  generateNewPDF(): void {
    const doc = new jsPDF();
    let startY = 20;
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();

    const headers = ['Sr No.', 'Meeting In', 'Meeting Date', 'Meeting Time', 'Meeting Representative', 'Action'];
    const columnWidths = [15, 35, 40, 40, 40, 20]; // Adjusted widths for portrait (195)

    const columns = this.results.map((user, index) => [
      (index + 1).toString(),
      user.meeting_in,
      new Date(user.meeting_date).toLocaleDateString('en-GB'), // Format date as dd-MM-yyyy
      user.meeting_time,
      user.representative,
      'View'
    ]);

    // Add Title
    doc.setFontSize(14);
    doc.text('Meeting Report', pageWidth / 2, startY, { align: 'center' });
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

    doc.save('MeetingReport.pdf');
  }
}
