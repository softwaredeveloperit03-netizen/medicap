import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import jsPDF from 'jspdf';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe],
})
export class DashboardComponent implements OnInit {
  results;
  material_type = '';
  from_date = '';
  to_date = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.loggedInDept = localStorage.getItem('department');
    this.from_date = this.datePipe.transform(Date.now(), '01-MM-yyyy');
    this.to_date = this.datePipe.transform(Date.now(), 'dd-MM-yyyy');
  }

  ngOnInit(): void {
    this.getRawControlSamples();
    this.get_rights();
  }

  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//
  getRawControlSamples() {
    this.service
      .get(
        'rnd/qa/controlsample.php?type=getRawControlSamples&material_type=' +
          this.material_type +
          '&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe(
        (response) => {
          console.log(response); // Debugging line
          if (response) {
            this.results = response;
          } else {
            console.error('No data returned from the API');
          }
        },
        (error) => {
          console.error('Error fetching raw control samples:', error);
        }
      );
  }

  download() {
    this.service.open('rnd/qa/controlsample.php?type=downloadRawControlSamples&material_type=' + this.material_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }

  // generateNewPDF(): void {
  //   console.log(this.results); // Debugging line
  //   if (!this.results || this.results.length === 0) {
  //     console.error('No data available to generate the PDF.');
  //     return;
  //   }

  //   const doc = new jsPDF();
  //   let startY = 20;
  //   const pageWidth = doc.internal.pageSize.getWidth();
  //   const pageHeight = doc.internal.pageSize.getHeight();

  //   const headers = [
  //     'Sr.',
  //     'Material Name',
  //     'Material type',
  //     'Material Code',
  //     'Medicap Lot No',
  //     'Grade',
  //     'Medicap Lot No',
  //     'Mfg Date',
  //     'Exp Date',
  //     'Stored Qty',
  //     'Available Qty',
  //     'Withdrawal Qty',
  //   ];

  //   // Define custom widths for each column
  //   const columnWidths = [10, 30, 25, 25, 20, 20, 20, 25, 25, 25, 25, 30];
  //   const totalWidth = columnWidths.reduce((a, b) => a + b, 0);

  //   // Calculate starting x positions for each column
  //   const columnXPos = columnWidths.map((width, index) => {
  //     return index === 0 ? 10 : columnXPos[index - 1] + columnWidths[index - 1];
  //   });

  //   // Add Title
  //   doc.setFontSize(14);
  //   doc.text('Material Report', pageWidth / 2, startY, { align: 'center' });
  //   startY += 10;

  //   // Add Table Headers
  //   doc.setFontSize(10);
  //   doc.setFillColor(14, 67, 112);
  //   doc.setTextColor(255, 255, 255);
  //   doc.rect(10, startY, totalWidth, 10, 'F');
  //   headers.forEach((header, index) => {
  //     doc.text(header, columnXPos[index] + 2, startY + 7);
  //   });
  //   startY += 10;

  //   // Add Table Rows
  //   doc.setTextColor(0, 0, 0);
  //   this.results.forEach((result, rowIndex) => {
  //     const row = [
  //       (rowIndex + 1).toString(),
  //       result.material_name,
  //       result.material_subtype,
  //       result.material_code,
  //       result.batch_no,
  //       result.grade,
  //       result.ar_no,
  //       result.mfg_date
  //         ? new Date(result.mfg_date).toLocaleDateString('en-GB')
  //         : '',
  //       result.exp_date
  //         ? new Date(result.exp_date).toLocaleDateString('en-GB')
  //         : '',
  //       `${result.sample_quantity} ${result.unit}`,
  //       result.avl_qty,
  //       result.withdrawal_qty,
  //     ];

  //     row.forEach((cell, cellIndex) => {
  //       doc.text(cell.toString(), columnXPos[cellIndex] + 2, startY + 7);
  //     });
  //     startY += 10;

  //     // Check if we need to add a new page
  //     if (startY > pageHeight - 20) {
  //       doc.addPage();
  //       startY = 20;
  //     }
  //   });

  //   doc.save('MaterialReport.pdf');
  // }
}
