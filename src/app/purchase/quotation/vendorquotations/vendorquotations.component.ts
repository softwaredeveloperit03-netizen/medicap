import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-vendorquotations',
  templateUrl: './vendorquotations.component.html',
  styleUrls: ['./vendorquotations.component.css']
})
export class VendorquotationsComponent implements OnInit {
  vendors;

  isView = false;
  results;
  selectedResult: [];
  to_date='';
  from_date='';
  vendor_no='';
  isEdit=false;
  today='';
  grades;
  chemicals;
  glasswares;

  constructor(private service: DataAccessService) { 
  }

  ngOnInit() {
    this.getQuotationLog();
    this.getVendors();
    this.getGrades();
  }

  getQuotationLog(){
    this.service.get('vendor-panel/product.php?type=getQuotationLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  getVendors(){
    this.service.get('common.php?type=getVendors').subscribe((response:any) => {
      this.vendors = response;
    });
  }

  getChemicals() {
    this.service.get('common.php?type=getChemicals').subscribe(response => {
      this.chemicals = response;
    });
  }

  getGlasswares() {
    this.service.get('common.php?type=getGlasswares').subscribe(response => {
      this.glasswares = response;
    });
  }

  getGrades(){
    this.service.get('common.php?type=getGrades').subscribe((response:any) => {
      this.grades = response;
    });
  }

  viewQuotations(index){
  }

}
