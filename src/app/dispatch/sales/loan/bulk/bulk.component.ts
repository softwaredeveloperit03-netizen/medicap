import { Component, OnInit } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-bulk',
  templateUrl: './bulk.component.html',
  styleUrls: ['./bulk.component.css']
})
export class BulkComponent implements OnInit {

  clients;
  selectedClient = [];
  branches = [];
  batches =[];
  materials;
  client_code='';
  branch='';
  batch='';
  gross_total = '0';
  other_charge_amt = '0';
  disc_total = '0';
  taxable = '0';
  tax_total = '0';
  net_total = '0';
  sgst_amt = '0';
  igst_amt= '0' ;
  cgst_amt= '0';
  RndOffAmt = '0';
  TotInvVal = '0';
  TotInvValFc = '0';
  selectedBranch = [];
  selectedBatch = [];
  clientsData=[];
  constructor(private service:DataAccessService,private router:Router) { 
  }

  ngOnInit(): void {
    this.getClients();
    this.getMaterials();
   // this.getBatch();
  }

  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.clients = response;
    });
  }

  getBranch(index){
    index = index - 1;
    if (index !== -1) {
      this.clientsData=this.clients[index];
      console.log('client', this.clientsData);
      this.branches = this.clients[index].branch;
    }
  }
  getBatch(index){
    index = index - 1;
    if (index !== -1) {
      this.clientsData=this.clients[index];
      console.log('client', this.clientsData);
      this.batches = this.clients[index].BA;
    }
  }


  selectBranch(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedBranch = this.branches[index];
    }
  }

  selectBatch(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedBatch = this.batches[index];
    }
  }
  getMaterials(){
    this.service.get('dispatch/sales.php?type=getMaterials').subscribe(response=>{
      this.materials = response;

      for (let i = 0; i < this.materials.length; i++) {
        let material = this.materials[i];
        material['selected'] = false;
        material['disc_per'] = 0;
        material['purchase_qty'] = 0;
        material['f_qty'] = 0;
        material['other_charge'] = 0;
        material['sale_rate'] = 0;
        material['taxable']=0;
        material['gross_total'] = 0;
        material['disc_total'] = 0;
        material['tax_total'] = 0;
        material['net_total'] = 0;
        material['gst_amt'] = 0;
        material['cgst_amt'] = 0;
        material['sgst_amt'] = 0;
        material['Igst_amt'] = 0;
        this.materials[i] = material;
      }
    });
  }
  
  keyPressNumbers(event) {
    var charCode = (event.which) ? event.which : event.keyCode;
    // Only Numbers 0-9
    if ((charCode < 48 || charCode > 57)) {
      event.preventDefault();
      return false;
    } else {
      return true;
    }
  }

  onChange(event, index) {
    let material = this.materials[index];
    if (event) {
      material['selected'] = true;
    } else {
      material['selected'] = false;
    }
    this.materials[index] = material;
    this.calculation(index);
  }

  calculation(index) {
    let material = this.materials[index];
    if (+material['qty'] < +material['purchase_qty']) {
      alertify.success('Available Qty: ' + +material['qty']);
      material['purchase_qty'] = +material['qty'];
    }
    let gross_total = +material['purchase_qty'] * +material['sale_rate'];
    let disc_total = (gross_total * +material['disc_per']) / 100;
    let taxable = gross_total - disc_total;
    let gst_total = (taxable * +material['gst']) / 100;
    let net_total = taxable + gst_total + +material['other_charge'];
    material['gross_total'] = parseFloat(gross_total + '').toFixed(2);
    material['disc_total'] = parseFloat(disc_total + '').toFixed(2);
    material['taxable'] = parseFloat(taxable + '').toFixed(2);
    material['gst_total'] = parseFloat(gst_total + '').toFixed(2);
    material['sgst_amt'] = +material['gst_total'] / 2;
    material['cgst_amt'] = +material['gst_total'] / 2;
    material['igst_amt'] = +material['gst_total'];
    material['net_total'] = parseFloat(net_total + '').toFixed(2);

   
     
    ///total calculation
    gross_total = 0;
    disc_total = 0;
    taxable = 0;
    net_total = 0;
    let sgst_amt = 0;
    let cgst_amt = 0;
    let igst_amt = 0;
    let other_charge_amt = 0;
    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      if (material['selected']) {
        gross_total += +material['gross_total'];
        disc_total += +material['disc_total'];
        taxable += +material['taxable'];
        sgst_amt += +material['sgst_amt'];
        cgst_amt += +material['cgst_amt'];
        igst_amt += +material['igst_amt'];
        net_total += +material['net_total'];
        other_charge_amt += +material["other_charge"];
      }
    }
    this.gross_total = parseFloat(gross_total + '').toFixed(2);
    this.disc_total = parseFloat(disc_total + '').toFixed(2);
    this.taxable = parseFloat(taxable + '').toFixed(2);
    this.sgst_amt = parseFloat(sgst_amt + '').toFixed(2);
    this.cgst_amt = parseFloat(cgst_amt + '').toFixed(2);
    this.igst_amt = parseFloat(igst_amt + '').toFixed(2);
    this.net_total = parseFloat(net_total + '').toFixed(2);
    this.other_charge_amt = parseFloat(other_charge_amt + '').toFixed(2);

    this.TotInvValFc = this.net_total;
    this.TotInvVal = Math.round(+this.net_total) + '';
    this.RndOffAmt = parseFloat((+this.TotInvVal - +this.TotInvValFc) + '').toFixed(2);
  }

  saveData(data, data1){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    if (!data1.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;

    let products = [];
    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      if (material['selected']) {
        products[products.length] = material;
      }
    }

    if (products.length == 0) {
      alertify.error('Select atleast 1 product');
      return;
    }
    temp['client_code']=this.client_code;
    temp['gst_type'] = this.clientsData['gst_type'];
    temp['branch']=this.branch;
    temp['materials'] = products;
    temp['company'] = this.clientsData['LglNm'];
    temp['gross_total']=this.gross_total;
    temp['disc_total']=this.disc_total;
    temp['taxable'] = this.taxable;
    temp['net_total']=this.net_total;
    temp['SgstVal']=this.sgst_amt;
    temp['CgstVal']=this.cgst_amt;
    temp['IgstVal']=this.igst_amt;
    temp['OthChrg']=this.other_charge_amt;
    temp['tax_total']=this.tax_total;
    temp['RndOffAmt']=this.RndOffAmt;
    temp['TotInvVal']=this.TotInvVal;
    temp['TotInvValFc']=this.TotInvValFc;

    this.service.post('dispatch/sales.php?type=saveBulkOrder',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Bulk Material save succesfuly');
        this.router.navigate(['/dispatch/sales/own']);
      }else{
        alertify.error('Some Error Occured');
      }
    });
  }
}
