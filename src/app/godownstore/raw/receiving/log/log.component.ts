 import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]

})
export class LogComponent implements OnInit {
  checklist:any=[];
  challan_for='';
  from_date = '';
  today='';
  to_date = '';
  isView = false;
  results;
  vendor_no='';
  material_type='';
  material_name='';
  receiving_date='';
  status='';
  selectedReport = [];
  vendors;
  damage= [];
  receiving_details= [];
  batches= [];
  material_subtype='';
  plant_id:any;
  @ViewChild('printableContent') printableContent: ElementRef;

  constructor(private service: DataAccessService,private datePipe: DatePipe,private elementRef: ElementRef) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getReceivingLog();
    this.getVendors();
    
  }

  getReceivingLog() {
    this.service.get('store/raw.php?type=getReceivingLog&material_subtype='+this.material_subtype+'&from_date='+this.from_date + '&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.damage = this.selectedReport['receiving_details'];
    this.receiving_details = this.selectedReport['receiving_details'];
    this.batches = this.selectedReport['batches'];
    console.log(this.damage)
    this.isView = true;
    this.getChkListData();
  }

  viewfile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
  viewfile1(){
    this.service.open('store/raw.php?type=tankerRecordPDF&challan_no='+this.selectedReport['challan_no']);
  }
  viewfile2(){
    this.service.open('store/raw.php?type=checklistRecordPDF&challan_no='+this.selectedReport['challan_no']);
  }
  downloadPDF(sign){
    this.service.open('store/raw.php?type=receivingMaterialPDF&pdfsign='+sign+'&id=' + this.selectedReport['id']);
  }
  download_dg_PDF(sign){
    this.service.open('store/raw.php?type=receiving_digital_MaterialPDF&pdfsign='+sign+'&id=' + this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('store/raw.php?type=receivingMaterialLogPDF&material_type='+this.material_type+'&from_date='+this.from_date+'&to_date='+this.to_date+'&challan_for='+this.challan_for)
  }

  AllRecord(){
    this.service.get('store/raw.php?type=getAllReceivingLog').subscribe((response : any) => {
      this.results = response;
    });
    this.material_subtype='';
  }
  getChkListData() {
    this.service.get('store/raw.php?type=get_chlist&receiving_no='+this.selectedReport['receiving_no']).subscribe(response => {
      this.checklist = response;
    });
  }

  downloadPDF1() {
    // Get the printable content
    const printableContent = this.printableContent.nativeElement.innerHTML;

    // Create a new Blob
    const blob = new Blob([this.createPdfContent(printableContent)], { type: 'application/pdf' });

    // Create a download link for the Blob
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'datagrid.pdf'; // Set the filename
    link.click();

    // Clean up
    window.URL.revokeObjectURL(url);
  }
  
  createPdfContent(htmlContent: string): string {
    // Include Clarity CSS styles
    const clarityStyles = `
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@clr/ui/clr-ui.min.css">
    `;

    // Create PDF content with Clarity styles
    return `
      <html>
        <head>
          <title>Printable PDF</title>
          ${clarityStyles}
          <style>
            @media print {
              /* Hide unnecessary elements */
              button {
                display: none;
              }
            }
          </style>
        </head>
        <body>
          ${htmlContent}
        </body>
      </html>
    `;
  }
  downloadExcel () {
const dataToExport = this.results.map(user => ({
      'Sr.': this.results.indexOf(user) + 1,
      'Receiving Date': user.receiving_date,
      'Receiving No': user.document_no,
      'Challan No': user.challan_no,
      'Material Type': user.material_subtype,
      'Grade': user.gradeName,
      'Material Code': user.material_code,
      'Material Name': user.material_name,
      'No Of Containers': user.containers,
      'Short / Extra Qty': user.qty_status,
      'Qty Received': user.received_qty + user.unit
    }));

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Sheet1');

    XLSX.writeFile(workbook, 'datagrid_export.xlsx');
  }

}
