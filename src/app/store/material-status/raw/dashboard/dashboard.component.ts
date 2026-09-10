import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'quarantine', title: 'Quarantine Stock', route: 'quarantine', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'test', title: 'Under Test Stock', route: 'test', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'approved', title: 'Approved Stock', route: 'approved', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'approved', title: 'On Hold Stock', route: 'approved', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'quarantine', title: 'Rejected Stock', route: 'quarantine', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
  ];


  stocks;
  vendors;
  loading;
  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];
  material_for='';
  vendor_no = '';
  material_type = '';
  grade = '';
  status = '';
  material_name = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getAllStock();/* 
    this.getMaterials(); */
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
/* 
  getMaterials(){
    this.service.get('store.php?type=rawmateriallist').subscribe((response:any) => {
      this.materiallist = response;
    });
  } */

  getAllStock() {
    this.service.get('store/raw.php?type=getStock&status='+this.status+'&material_for='+this.material_for+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/raw.php?type=downloadStock&status='+this.status+'&material_for='+this.material_for+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name);
  }

}
