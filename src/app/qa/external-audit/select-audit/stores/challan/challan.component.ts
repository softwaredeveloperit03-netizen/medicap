import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-challan',
  templateUrl: './challan.component.html',
  styleUrls: ['./challan.component.css'],
  providers:[DatePipe]
})
export class ChallanComponent implements OnInit {

  isView = false;
  results;
  vendors;
  inward;
  selectedCountry = [];
  selectedResult = [];
  material_type='';
  from_date='';
  to_date='';
  today='';
  vendor_no='';
  status='approve';
  isAdd=false;
  inward_no: '';
  vendor_name: '';

  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getChallansLog();
    this.getVendors();
    // this. getInward();
  }

  getChallansLog(){
    this.service.get('store/challan.php?type=getChallansLog&material_type='+this.material_type  +'&to_date='+this.to_date +'&from_date='+this.from_date  +'&inward_no='+this.inward_no +'&vendor_name='+this.vendor_name).subscribe(response => {
      this.results = response;
      this.inward = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  // getInward() {
  //   this.service.get('common.php?type=getInward').subscribe(response => {
  //     this.inward = response;
  //   });
  // }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  downloadLog(){
    this.service.open('pdf1/store.php?type=challanLog')
  }
  download(){
    this.service.open('store/challan.php?type=downloadChallanLog&vendor_no='+this.vendor_no  +'&to_date='+this.to_date +'&from_date='+this.from_date +'&status='+status)
  }

  upload(){
    if(this.selectedResult['challan_file']==''){
      alertify.error("file not available");
    }else
    window.open(this.service.url+'upload/challan/'+this.selectedResult['challan_file']);
  }


  add(index){
    this.isAdd=true;
  }


  modify(data){
    if (!data.valid) {
      alertify.warning('All fields are required!');
      return;
    }
     this.service.post('store/shortage.php?type=editLevel&id='+this.selectedResult["id"],JSON.stringify(this.selectedResult)).subscribe(response=>{
        if(response['status']=='success'){
          alertify.success('Comments Added  Successuly');
          this.isAdd=false;
        }else{
          alertify.error('some error occured');
        }
      });
    }





}
