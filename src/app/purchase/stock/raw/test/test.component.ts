import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-test',
  templateUrl: './test.component.html',
  styleUrls: ['./test.component.css']
})
export class TestComponent implements OnInit {

  stocks;
  vendors;
  material_for='';

  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];
  item = [];
  vendor_no = '';
  material_type = '';
  grade = '';
  status = 'under test';
  material_name = '';
  grades;
  materials;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getStock();
    this. getVendors();
    this.getMaterials();
    this.service.observableGrade.subscribe(response =>{
      this.grades = response;
    });
  }

  getStock() {
    this.service.get('store/raw.php?type=getStock&status=Under Test').subscribe(response => {
      this.stocks = response;
      this.filterItem();
    });
  }
  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials = response;
    })
  }
  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/raw.php?type=downloadRawtest&material_name='+ this.material_name);
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.stocks.length; i++) {
      let material = this.stocks[i];
      if (material['material_name'].toUpperCase().includes(this.material_name.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
  AllRecord(){
    this.item =this.stocks;
    this.material_name='';
    
}
exportToExcel(): void {
  const fileName = 'stock_data.xlsx';
  const header = ['Sr.', 'Receiving no', 'Medicap Lot No', 'Vendor Name', 'Material Type', 'Material Code', 'Material Name', 'Grade', 'Received Qty', 'Sampling Date', 'Testing Status'];
  const data = [header, ...this.item.map((stock, index) => [
    index + 1,
    stock.grn_no,
    stock.medicap_lot_no || stock.batch_no || stock.ar_no,
    stock.vendor_name,
    stock.material_subtype,
    stock.material_code,
    stock.material_name,
    stock.grade,
    `${stock.qty} ${stock.unit}`,
    stock.sampling_start_time,
    stock.status
  ])];

  const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);
  const wb: XLSX.WorkBook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Stock Data');
  XLSX.writeFile(wb, fileName);
}
}
