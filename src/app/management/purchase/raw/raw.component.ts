import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
declare let alertify: any;

@Component({
  selector: 'app-raw',
  templateUrl: './raw.component.html',
  styleUrls: ['./raw.component.css'],
  providers:[DatePipe]

})
export class RawComponent implements OnInit {



  from_date='';
  to_date='';
  today='';





  constructor(private service: DataAccessService,private datePipe:DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getAllPendingPO();
  }

 

 
  po_type = 'Raw Material';
  results;

  getAllPendingPO() {
    this.service.get('purchase/po/raw.php?type=getAllPOLog&po_type=' + this.po_type).subscribe((response) => {
        this.results = response;
    });
  }



  isView = false;
  selectedBill = [];
  selectedShip = [];

  view(data) {
    this.selectedPO = data;
    this.selectedBill = this.selectedPO['selectedBill'];
    this.selectedShip = this.selectedPO['selectedShip'];
    this.isView = true;
  }

 

  updatePO(status) {
 
    let temp = {};
    temp['id'] = this.selectedPO['id'];
    temp['status'] = status;

    this.service.post('purchase/po/raw.php?type=approvePO', JSON.stringify(temp) ).subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('PO Sent For '+status+ ' Approval.....');
          this.getAllPendingPO();
          this.isView = false;
        } else {
          alertify.error('Failed to Update PO, Please try again!');
        }
      });
  }

   
  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
   
  
 

  
   calculatePercentage() {
    this.transportTaxAmt = (this.transportCost * this.gstPerOnTransport) / 100;
    this.totalTransportationCose = +this.transportCost + +this.transportTaxAmt;
  }
 
 
  download(){
    this.service.open('purchase/po/raw.php?type=downloadPOLog&from_date='+this.from_date+'&to_date='+this.to_date)
  }

  downloadPOReport(){
    this.service.open('purchase/po_print.php?type=downloadPOReport&id='+this.selectedPO['id']);
  }


 
  purchaseOrderLogActionForStatusUpdate(status,what) {

    let check = confirm("Are you sure you want to " + what + " this Purchase Order?");
    if (check) {
      let updateRemark = prompt("Please Enter "+ what + " Remark.....");
      let obj ={};
      obj['what'] = what;
      obj['status'] = status;
      obj['updateRemark'] = updateRemark;
      obj['id'] = this.selectedPO['id'];

      this.service.post('purchase/po/raw.php?type=purchaseOrderLogActionForStatusUpdate',JSON.stringify(obj)).subscribe(response => {
        if (response['status'] == 'success') {
          alertify.success('Purchase Order '+ what + ' Successfully!!!!!!!');
          this.isView = false;
          this.getAllPendingPO();
        } else {
          alertify.error(response['status']);
        }
      });

    }

  }



  isLanding =  false;
  selectedPO= [];
  totalLandingCost = 0;

  transportCost = 0;
  gstPerOnTransport = 0;
  totalTransportationCose = 0;
  transportTaxAmt = 0;
  landingCost = 0;


  viewLandingPrice(data) {  
    let totalQty  = 0;
    this.transportCost = 0;
    this.gstPerOnTransport = 0;
    this.totalTransportationCose = 0;
    this.transportTaxAmt = 0;
 

    this.selectedPO = data;
    
    totalQty = this.selectedPO['materials'].reduce((sum, material) => sum + +material.qty, 0);

    this.isLanding = true;

    this.totalLandingCost = +this.selectedPO['final_total'] ;
    this.landingCost = parseFloat((this.totalLandingCost / totalQty).toFixed(2));
  }

 
  transportCosts = [];


  totalTransAmt = 0;
  totalTransGstAmt = 0;
  finalTransAmt = 0;


  addTransportCost(data){
    if(!data.valid){
      alertify.error('All Field Required !!!!!!!!!!');
      return;
    }
    let temp = data.value;
    this.transportCosts.push(temp);

    this.totalTransAmt = parseFloat((this.totalTransAmt + +temp['transportCost']).toFixed(2));
    this.totalTransGstAmt = parseFloat((this.totalTransGstAmt + +temp['transportTaxAmt']).toFixed(2));
    this.finalTransAmt = parseFloat((this.finalTransAmt + +temp['totalTransportationCose']).toFixed(2));
    data.reset();
  }

  delTraspotCost(index){
    this.totalTransAmt = parseFloat((this.totalTransAmt - +this.transportCosts[index]['transportCost']).toFixed(2));
    this.totalTransGstAmt = parseFloat((this.totalTransGstAmt - +this.transportCosts[index]['transportTaxAmt']).toFixed(2));
    this.finalTransAmt = parseFloat((this.finalTransAmt - +this.transportCosts[index]['totalTransportationCose']).toFixed(2));
    this.transportCosts.splice(index , 1);
  }


  saveTransportCost(){

    let temp = {};
    temp['poId'] = this.selectedPO['poId'];
    temp['transportCosts'] = this.transportCosts;
    temp['transportCost'] = this.totalTransAmt;
    temp['transportTaxAmt'] = this.totalTransGstAmt;
    temp['totalTransportationCose'] = this.finalTransAmt;
 
    this.service.post('purchase/po/raw.php?type=saveTransportCost',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Transport Cost Saved Succeffully....');
        this.isLanding = false;
        this.getAllPendingPO();
        this.transportCosts = [];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  


  }


