import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-quarantine',
  templateUrl: './quarantine.component.html',
  styleUrls: ['./quarantine.component.css']
})
export class QuarantineComponent implements OnInit {

  stocks;
  vendors;
  item = [];
  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  vendor_no='';
  material_type = '';
  grade = '';
  status = 'quarantine';
  material_name = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getStock();
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getStock() {
    this.service.get('store/packing.php?type=getStock&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
      this.filterItem();
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/packing.php?type=downloadStock&&vendor_no='+this.vendor_no);
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.stocks.length; i++) {
      let material = this.stocks[i];
      if (material['vendor_no'].toUpperCase().includes(this.vendor_no.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
  exportToExcel(): void {
    const fileName = 'stock_data.xlsx';
  
    // Define the header row
    const header = ['Sr.', 'Challan For', 'Receiving no', 'Vendor Name', 'Material Type', 'Material Code', 'Material Name', 'Grade', 'Medicap Lot No', 'Received Qty', 'Damage Containers', 'Received Date'];
  
    // Define the data rows
    const data = [header, ...this.item.map((stock, index) => [
      index + 1,
      stock.challan_for,
      stock.grn_no,
      stock.vendor_name,
      stock.material_subtype,
      stock.material_code,
      stock.material_name,
      stock.grade,
      stock.batch_no,
      `${stock.qty} ${stock.unit}`,
      stock.damage_container,
      this.formatDate(stock.received_date)
    ])];
  
    // Create a new workbook and add the data to a worksheet
    const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);
  
    // Create a new workbook and add the worksheet
    const wb: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Stock Data');
  
    // Save the workbook as an Excel file
    XLSX.writeFile(wb, fileName);
  }
  
  formatDate(date: any): string {
    if (date) {
      const formattedDate = new Date(date);
      const day = formattedDate.getDate().toString().padStart(2, '0');
      const month = (formattedDate.getMonth() + 1).toString().padStart(2, '0'); // Months are zero-based
      const year = formattedDate.getFullYear();
      return `${day}-${month}-${year}`;
    }
    return '';
  }
}
