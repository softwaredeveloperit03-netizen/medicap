import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  vendors;
  glassware;
  units;
  client;
  materialList=[];
  selectedchemical=[];
  selectedVendor=[];
  selectedclient=[];
  isown=false;
  isthirdParty=false;
  isLoanLicense=false;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getVendors();
    this.getGlassware();
    this.getUnits();
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getGlassware() {
    this.service.get('common.php?type=getGlasswares').subscribe(response => {
      this.glassware = response;
    });
  }

  getUnits() {
    this.service.get('qa.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }
  
  getchem(index){
    index=index-1;
    this.selectedchemical=this.glassware[index];
  }

  getven(index){
    index= index-1;
    this.selectedVendor=this.vendors[index];
  }
  getClient(value){
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.client = response;
    });
  }

  getclient(index){
    index = index-1;
    this.selectedclient=this.client[index];
  }

  getDetails(show){
   if(show == 'Loan License' || show=='Third Party'){
      this.getClient('show');
      this.isown=false;
      this.isthirdParty=true;
      this.isLoanLicense=true;
    }else if(show  =='own'){
      this.isown=true;
      this.isthirdParty=false;
      this.isLoanLicense=false;
    }
  }



  addform(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp=data.value;
    temp['glassware_name']=this.selectedchemical['name'];
    temp['client_code']=this.selectedclient['client_code'];
    temp['client_name']=this.selectedclient['company'];
    temp['vendor_name']=this.selectedVendor['vendor_name'];
    this.materialList[this.materialList.length]=temp;
    data.reset();
  }

  deletelist(index){
    this.materialList.splice(index,1);
  }

  save() {
    this.service.post('purchase/indend/glassware.php?type=saveIndend', JSON.stringify(this.materialList)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('data Saved Successfully');
        this.router.navigate(['/qc/indend/glassware']);
      } else {
        alertify.error('An error occured, Please try again!');
      }
    });
  }

}
