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
  chemicals;
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
    this.getChemicals();
    this.getUnits();
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getChemicals() {
    this.service.get('common.php?type=getChemicals').subscribe(response => {
      this.chemicals = response;
    });
  }

  getUnits() {
    this.service.get('qa.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }
  
  getchem(index){
    index=index-1;
    this.selectedchemical=this.chemicals[index];
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
    temp['chemical_name']=this.selectedchemical['chemical_name'];
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
    this.service.post('purchase/indend/chemical.php?type=saveIndend', JSON.stringify(this.materialList)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('data Saved Successfully');
        this.router.navigate(['/qc/indend/chemical']);
      } else {
        alertify.error('An error occured, Please try again!');
      }
    });
  }

}
