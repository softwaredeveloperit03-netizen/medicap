import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  client_type = 'Own';
  combikit = 'No';
  dosages = [];
  selectedDosage = [];
  configuration = [];

  mono = '';
  outer = '';
  shrink = '';

  units = [];
  packings = [];
  dosage = '';

  primarymaterialList=[];
  primarymaterial;
  primaryqty = 0;
  primaryover = 0;
  primarytotal =0;

  secondarymaterialList=[];
  secondaryMaterial;
  secondaryqty = 0;
  secondaryover = 0;
  secondarytotal =0;

  tertiarymaterialList=[];
  tertiaryMaterial;
  tertiaryqty = 0;
  tertiaryover = 0;
  tertiarytotal =0;

  otherMaterialList=[];
  otherMaterial;
  otherqty = 0;
  otherover = 0;
  othertotal =0;

  othermaterialList = [];

  packingList=[];

  clients;
  division;
  products;
  selectedProd=[];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDosages();
    this.getclients();
    this.getPrimaryMaterial();
    this.getSecondaryMaterial();
    this.getTertiaryMaterial();
    this.getOtherMaterial();
  }

  getDosages() {
    this.service.get('packing/workorder.php?type=getDosages').subscribe((response: any) => {
      this.dosages = response;
    });
  }

  selectDosage(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedDosage = this.dosages[index];
      this.configuration = this.dosages[index].configuration;
      this.getProducts();
    } else {
      this.configuration = [];
    }
  }

  getProducts() {
    this.service.get('common.php?type=getProductsByDosage&dosage_form=' + this.selectedDosage['dosage_form']).subscribe(response => {
      this.products = response;
    });
  }

  getproductdetails(index) {
    index = index - 1;
    if (index != -1) {
      this.selectedProd = this.products[index];
    } else {
      this.selectedProd = [];
    }
  }

  getclients(){
    this.service.get('packing/workorder.php?type=clientlst').subscribe(response=>{
      this.clients  = response;
    });
  }

  getdivision(index){
    this.division  = this.clients[index].division;
  }

  totalqtycln(){
    this.primarytotal = this.primaryqty*1 + (this.primaryqty*this.primaryover/100);
    this.secondarytotal = this.secondaryqty*1 + (this.secondaryqty*this.secondaryover/100);
    this.tertiarytotal = this.tertiaryqty*1 + (this.tertiaryqty*this.tertiaryover/100);
  }

  getPrimaryMaterial() {
    this.service.get('packing/workorder.php?type=primaryMaterial').subscribe(response => {
      this.primarymaterial = response;
    });
  }
  primaryadd(data) {
    this.primarymaterialList[this.primarymaterialList.length] = data.value;
    data.resetForm();
  }
  primarydele(index) {
    this.primarymaterialList.splice(index, 1);
  }

  getSecondaryMaterial() {
    this.service.get('packing/workorder.php?type=secondaryMaterial').subscribe(response => {
      this.secondaryMaterial = response;
    });
  }
  secondaryadd(data) {
    this.secondarymaterialList[this.secondarymaterialList.length] = data.value;
    data.resetForm();
  }
  secondarydele(index) {
    this.secondarymaterialList.splice(index, 1);
  }

  getTertiaryMaterial() {
    this.service.get('packing/workorder.php?type=tertiaryMaterial').subscribe(response => {
      this.tertiaryMaterial = response;
    });
  }
  tertiaryadd(data) {
    this.tertiarymaterialList[this.tertiarymaterialList.length] = data.value;
    data.resetForm();
  }
  tertiarydele(index) {
    this.tertiarymaterialList.splice(index, 1);
  }

  getOtherMaterial(){
    this.service.get('packing/workorder.php?type=otherMaterial').subscribe(response => {
      this.otherMaterial = response;
    });
  }
  otheradd(data) {
    this.otherMaterialList[this.otherMaterialList.length] = data.value;
    data.resetForm();
  }
  otherdele(index) {
    this.otherMaterialList.splice(index, 1);
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;

    if (this.mono) {
      temp['mono'] = 'yes';
    }

    if (this.outer) {
      temp['outer'] = 'yes';
    }

    if (this.shrink) {
      temp['shrink'] = 'yes';
    }

    temp['primary_material'] = this.primarymaterialList;
    temp['secondary_material'] = this.secondarymaterialList;
    temp['tertiary_material'] = this.tertiarymaterialList;
    temp['other_material'] = this.otherMaterialList;
    
    this.service.post('packing/workorder.php?type=saveWorkOrder', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record saved successfully');
        data.resetForm();
        this.primarymaterialList = [];
        this.secondarymaterialList = [];
        this.tertiarymaterialList = [];
        this.otherMaterialList = [];
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
