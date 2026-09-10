import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-bulk',
  templateUrl: './bulk.component.html',
  styleUrls: ['./bulk.component.css']
})
export class BulkComponent implements OnInit {

  purchase_qty = '';
  clients;
  selectedClient = [];
  branches = [];

  materials;
  client_code='';
  branch='';
  gross_total = '0';
  disc_total = '0';
  tax_total = '0';
  net_total = '0';

  selectedBranch = [];
  productList = [];
  batches = [];
  mat = [];
  constructor(private service:DataAccessService,private router:Router) { 
  }

  ngOnInit(): void {
    this.getClients();
    this.getMaterials();
  }

  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.clients = response;
    });
  }

  getBranch(index){
    index = index - 1;
    if (index !== -1) {
      this.branches = this.clients[index].branch;
    }
  }

  selectBranch(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedBranch = this.branches[index];
    }
  }

  getMaterials(){
    this.service.get('account/sales.php?type=getMaterials').subscribe(response=>{
      this.materials = response;

      for (let i = 0; i < this.materials.length; i++) {
        let material = this.materials[i];
        material['selected'] = false;
        material['disc_per'] = 0;
        material['purchase_qty'] = 0;
        material['f_qty'] = 0;
        material['sale_rate'] = 0;
        material['gross_total'] = 0;
        material['disc_total'] = 0;
        material['tax_total'] = 0;
        material['net_total'] = 0;
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
    let net_total = taxable + gst_total;
    material['gross_total'] = parseFloat(gross_total + '').toFixed(2);
    material['disc_total'] = parseFloat(disc_total + '').toFixed(2);
    material['taxable'] = parseFloat(taxable + '').toFixed(2);
    material['gst_total'] = parseFloat(gst_total + '').toFixed(2);
    material['net_total'] = parseFloat(net_total + '').toFixed(2);
    this.materials[index] = material;

    gross_total = 0;
    disc_total = 0;
    let tax_total = 0;
    net_total = 0;
    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      if (material['selected']) {
        gross_total += +material['gross_total'];
        disc_total += +material['disc_total'];
        tax_total += +material['tax_total'];
        net_total += +material['net_total'];
      }
    }
    this.gross_total = parseFloat(gross_total + '').toFixed(2);
    this.disc_total = parseFloat(disc_total + '').toFixed(2);
    this.tax_total = parseFloat(tax_total + '').toFixed(2);
    this.net_total = parseFloat(net_total + '').toFixed(2);
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
    temp['branch']=this.branch;
    temp['materials'] = products;
    temp['company'] = this.selectedClient['company'];
    temp['gross_total']=this.gross_total;
    temp['disc_total']=this.disc_total;
    temp['net_total']=this.net_total;
    temp['tax_total']=this.tax_total;
    this.service.post('dispatch/sales.php?type=saveBulkOrder',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Bulk Material save succesfuly');
        this.router.navigate(['/sales/own']);
      }else{
        alertify.error('Some Error Occured');
      }
    });
  }

  getBatches(Index) {}

}
