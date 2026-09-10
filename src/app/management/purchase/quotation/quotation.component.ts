import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-quotation',
  templateUrl: './quotation.component.html',
  styleUrls: ['./quotation.component.css'],
  providers:[DatePipe]
})
export class QuotationComponent implements OnInit {

  vendors;
  isView = false;
  results;
  selectedResult: [];
  to_date='';
  from_date='';
  vendor_no='';
  isEdit=false;
  today='';
  item=[];
  material_type='';
  gsts;
  isQEdit = false;
  entry_date='';

  constructor(private service: DataAccessService,private datePipe:DatePipe) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getQuotationLog();
    // this.getQuotationLogGeneral();
    this.getVendors();
    this.getGst();
  }

  getQuotationLog(){
    this.service.get('purchase/quotation.php?type=getQuotationLog').subscribe(response => {
      this.results = response;
      //this.filterVendor();
      console.log("qtlog");
      console.log(response);

    });
  }
  // getQuotationLogGeneral(){
  //   this.service.get('purchase/quotation.php?type=getQuotationLogGeneral').subscribe(response => {
  //     this.results = response;
  //     //this.filterVendor();
  //     console.log("qtlog");
  //     console.log(response);

  //   });
  // }
  getVendors(){
    this.service.get('common.php?type=getVendors').subscribe((response:any) => {
      this.vendors = response;
      console.log("getVendors");
      console.log(response);
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    // console.log(this.results[index]);
    this.isView = true;
  }
  uploadQuatation(url){
    url=this.service.url+'upload/quotation/'+url;
    window.open(url,'_blank');
    // window.open(this.selectedResult['documents']);
  }
  // uploadQuatation(){
  //   window.open(this.selectedResult['documents']);
  // }

  download(){
    this.service.open('purchase/quotation_log.php?type=downloadQuotationLog&vendor_no='+this.vendor_no+'&entry_date='+this.entry_date)
  }

  editRawMaterial(){

  }
  filterVendor() {
    this.item = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
    console.log(material);
      if (material['vendor_no']?.toUpperCase().includes(this.vendor_no?.toUpperCase()) &&       material.materials[0]['material_type']?.toUpperCase().includes(this.material_type.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
  getGst(){
    this.service.get('common.php?type=getGST').subscribe(response => {
      this.gsts = response;
    });
  }
  AllRecord(){
    this.vendor_no='';
    this.material_type='';
    this.filterVendor();
 }
 updateQuotation(data){

 }
 editData(){
   this.isEdit = false;
   this.isQEdit = true;
 }
}
