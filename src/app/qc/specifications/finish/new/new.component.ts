import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';
 declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe]
})
export class NewComponent implements OnInit {
  specification='Existing';
  control_sample;
  totalmicro_qty=0;
  products;
  grades;
  sample_qty;
  totalsample_qty=0;
  tests;
  subtests = [];
  composite = 0;
  micro_qty=0;
  units;
  unit = '';
  companies = [];
  subtest = '';
  revisionList = [];
  selectedProduct = [];
  isShowProd = false;
  test_type='';
  totalchemical_qty=0;
  sampling_plan='';
  sampling_drawn='';
  samples;
  
  sample_drawns;
  sample_withs;
  precautions;
  hazards;
  clients;
  selectedClient=[];
  clientList=[];
  today='';
  dosages;
  limit='';
  @Output() newSpec: EventEmitter<any> = new EventEmitter<any>();
  constructor(private service: DataAccessService, private datePipe:DatePipe,private route: ActivatedRoute,private router:Router) {
   this.today =this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    // this.getProducts();
    this.getGrades();
    this.getUnits();
    this.getStorage();
    this.getSample();
    this.getSampleDrawn();
    this.getSampleWithD();
    this.getHazards();
    this.getClients();
    this.getDosages();
  }


  getProductsByDosage(value) {
    this.service.get('qc/specification/finish.php?type=getProducts&product_type=' + value).subscribe(response => {
      this.products = response;
    });
  }


  getDosages(){
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages= response;
    });
  }
  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  }
  getClientName(index){
    index =index-1;
    if(index!==-1){
      this.selectedClient=this.clients[index];
    }
  }
  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  checkSpec(value) {
    if (value == 'New') {
      let date = this.formatDate(new Date());
      this.revisionList[this.revisionList.length] = {"spec_no": "AUTO GENERATE", "ver_no": "00", "change_mode": "NA", "change_reason": "NA", "effective_date": date};
    } else {
      this.revisionList = [];
    }
  }

  // getProducts(){
  //   this.service.get('qc/specification/finish.php?type=getProducts').subscribe(response => {
  //     this.products = response;
  //   });
  // }
  selectProduct(index) {
    index = index - 1;
    this.selectedProduct = this.grades[index];
    this.isShowProd = true;
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }
  getTests(value) {
    this.service.get('common.php?type=getTests&test_type='+ value).subscribe(response => {
      this.tests = response;
    });
  }
  getSubtest(event) {
    this.subtests = event.value.subtests;
  }

  getMicroCheck(value){
    this.service.get('common.php?type=getTests&test_type='+ value).subscribe(response => {
      this.tests = response;
    });
  }

  addexperience(data) {
    if (!data.valid) {
      alertify.error('Pharmacopoeial Test All fields are required!');
      return;
    }
    let temp = data.value;
    temp['test_type']=this.test_type;
    if (temp['limit'] == "Range") {
      temp['limits'] =  temp["lower_limit"] + temp["unit"] + " to " + temp["upper_limit"] + temp["unit"];
    } else if (temp['limit'] == "Not MoreThan") {
      temp['limits'] =  "NMT " + temp["lower_limit"] + temp["unit"];
    } else if (temp['limit'] == "Not LessThan") {
      temp['limits'] =  "NLT " + temp["upper_limit"] + temp["unit"];
    } else if (temp["limit"] == "Compliances") {
      temp["limits"] = "complies";
    } else if (temp["limit"] == "Description") {
      temp["limits"] = temp['descr'];
    }
    temp['test'] = temp['test'].test;
    temp['subtest'] = temp['subtest'].subtest;
    if(temp['test_type'] == 'Microbiology'){
      this.micro_qty += parseFloat(temp['sample_qty']);
    } else {
      this.composite += parseFloat(temp['sample_qty']);
    }
    this.companies[this.companies.length] = temp;
    data.resetForm();
    /* const element1 = document.getElementById('test') as HTMLElement;
    element1.focus(); */
  }
  addClient(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    if (temp['limit'] == "Range") {
      temp['limits'] =  temp["lower_limit"] + temp["unit"] + " to " + temp["upper_limit"] + temp["unit"];
    } else if (temp['limit'] == "LessThan") {
      temp['limits'] =  "NMT " + temp["lessthan"] + temp["unit"];
    } else if (temp['limit'] == "MoreThan") {
      temp['limits'] =  "NLT " + temp["morethan"] + temp["unit"];
    } else if (temp["limit"] == "Compliances") {
      temp["limits"] = "complies";
    } else if (temp["limit"] == "Description") {
      temp["limits"] = temp['descr'];
    }
    temp['TrdNm']=this.selectedClient['TrdNm'];
    temp['test'] = temp['test'].test;
    if(this.subtests?.length!==0){
      temp['subtest'] = temp['subtest'].subtest;
    }
    this.clientList[Object.keys(this.clientList).length] = temp;
    // this.clientList[this.clientList.length] = temp;
    data.resetForm();
  }
  deleteClient(index) {
    if (this.clientList[index].test_type == 'Microbiology') {
      this.micro_qty = this.micro_qty - parseFloat(this.clientList[index].sample_qty);
      this.clientList.splice(index, 1);
    } else if(this.clientList[index].test_type == 'Chemical') {
      this.composite = this.composite - parseFloat(this.clientList[index].sample_qty);
      this.clientList.splice(index, 1);
    }
  }

  formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) 
        month = '0' + month;
    if (day.length < 2) 
        day = '0' + day;

    return [year, month, day].join('-');
  }
  addRevision(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.revisionList[Object.keys(this.revisionList).length] = temp;
    data.resetForm();
    const element1 = document.getElementById('spec_no') as HTMLElement;
    element1.focus();
  }

  deleteProduct(index) {
    if (this.companies[index].test_type == 'Microbiology') {
      this.micro_qty = this.micro_qty - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    } else if(this.companies[index].test_type == 'Chemical') {
      this.composite = this.composite - parseFloat(this.companies[index].sample_qty);
      this.companies.splice(index, 1);
    }
  }


  deleteRevision(index) {
    this.revisionList.splice(index, 1);
  }

  saveFPSpecification(data) {
    // if (!data.valid) {
    //   alertify.error('All fields are required');
    //   return;
    // }
    // let test = data.value;
    // test['specification']=this.specification;
    // test['product_code'] = this.product_code;
    // test['tests'] = this.companies;
    // console.log('rrr', this.companies);
    // test['revisionHistory'] = this.revisionList;
    let temp = data.value;
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    // const uploadData = new FormData();
    // for (let key in temp) {
    //   let value = temp[key];
    //   uploadData.append(key, value);
    // }
    // if (this.selectedFile !== undefined) {
    //   uploadData.append('structural_formula', this.selectedFile, this.selectedFile.name);
    // }
    // uploadData.append('specification', this.specification);
    // uploadData.append('tests', JSON.stringify(this.companies));
    // uploadData.append('clientlist', JSON.stringify(this.clientList));
    // uploadData.append('revisionHistory', JSON.stringify(this.revisionList));
    temp['tests']=this.companies
    temp['clientlist']=this.clientList;
    temp['revisionHistory']=this.revisionList;
    this.service.post('qc/specification/finish.php?type=saveSpecification', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Finish Product Specification Saved Successfully');
        data.resetForm();
        this.router.navigate(['/qc/specifications/finish']);
      }else{
        alertify.error(response['status']);
      }
    });
  }

  selectedFile: File;
  isupload = false;
  onFilechange(event){
    if (event.target.files.length > 0) {
      this.selectedFile = event.target.files[0];
      this.isupload = true;
    } else {
      this.isupload = false;
    }
  }

  storages;
  isStorage = false;
  storage_condition = '';

  getStorage() {
    this.service.get('qa/master.php?type=getStorageConditions').subscribe(response => {
      this.storages = response;
    });
  }

  newStorage(value) {
    if (value == 'ADD NEW') {
      this.storage_condition = '';
      this.isStorage = true;
    }
  }

  saveStorage() {
    if (this.storage_condition.length !== 0) {
      this.service.get('qa/master.php?type=saveStorageCondition&storage_condition=' + this.storage_condition).subscribe(response => {
        if (response['status'] == 'success') {
          this.getStorage();
          this.isStorage = false;
          this.storage_condition = '';
          alertify.success('Storage life saved successfully');
        } else {
          alertify.error('Failed: An error occured');
        }
      });
    }
  }

  getSample() {
    this.service.get('master/master.php?type=getSamplingPlans').subscribe(response => {
      this.samples = response;
    });
  }

  selectedSamplingPlan: any[];
  filteredSamplingPlan: any[];
  filterSampling(event) {
    //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
    let filtered =[];
    let query = event.query;
    for (let i = 0; i < this.samples.length; i++) {
      let country = this.samples[i];
      if (country.sampling_plan.toLowerCase().indexOf(query.toLowerCase()) == 0) {
        filtered[filtered.length] = country;
      }
    }

    this.filteredSamplingPlan = filtered;
  }

  getSampleDrawn(){
    this.service.get('master/master.php?type=getSampleDrawnFroms').subscribe(response => {
      this.sample_drawns = response;
    });
  }

  selectedSamplingDrawn: any[];
  filteredSamplingDrawn: any[];
  filteredSamplingD(event) {
    //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
    let filtered =[];
    let query = event.query;
    for (let i = 0; i < this.sample_drawns.length; i++) {
      let country = this.sample_drawns[i];
      if (country.sample_drawn_from.toLowerCase().indexOf(query.toLowerCase()) == 0) {
        filtered[filtered.length] = country;
      }
    }
    this.filteredSamplingDrawn = filtered;
  }


  getSampleWithD(){
    this.service.get('master/master.php?type=getSampleDrawnWiths').subscribe(response => {
      this.sample_withs = response;
    });
  }

  selectedSamplingWith: any[];
  filteredSamplingWith: any[];
  filteredSamplingW(event) {
    //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
    let filtered =[];
    let query = event.query;
    for (let i = 0; i < this.sample_drawns.length; i++) {
      let country = this.sample_withs[i];
      if (country.sample_drawn_with.toLowerCase().indexOf(query.toLowerCase()) == 0) {
        filtered[filtered.length] = country;
      }
    }
    this.filteredSamplingWith = filtered;
  }

  getHazards(){
    this.service.get('master/master.php?type=getPrecautions').subscribe(response => {
      this.hazards = response;
    });
  }

  selectedHazards: any[];
  filteredHazards: any[];
  filteredH(event) {
    //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
    let filtered =[];
    let query = event.query;
    for (let i = 0; i < this.hazards.length; i++) {
      let country = this.hazards[i];
      if (country.precautions.toLowerCase().indexOf(query.toLowerCase()) == 0) {
        filtered[filtered.length] = country;
      }
    }
    this.filteredHazards = filtered;
  }


  addTests(data) {
    // console.log(data.value);
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    if (temp['limit'] == "Range") {
      temp['limits'] =  temp["lower_limit"] +" "+ temp["unit"] + " to " + temp["upper_limit"] + " " + temp["unit"];
    } else if (temp['limit'] == "Not LessThan") {
      temp['limits'] =  "NLT " + temp["upper_limit"] +" "+ temp["unit"];
    } else if (temp['limit'] == "Not MoreThan") {
      temp['limits'] =  "NMT " + temp["lower_limit"] +" "+ temp["unit"];
    } else if (temp["limit"] == "Compliances") {
      temp["limits"] = "complies";
    } else if (temp["limit"] == "Description") {
      temp["limits"] = temp['descr'];
    }
    temp['test'] = temp['test'].test;
  
    if(this.subtests?.length!==0){
      temp['subtest'] = temp['subtest'].subtest;
    }
    if(this.test_type=='Chemical'){
      this.totalchemical_qty=this.sample_qty*1+this.totalchemical_qty*1;
    }
    if(this.test_type=='Microbiology'){
      this.totalmicro_qty=this.sample_qty*1+this.totalmicro_qty*1;
    }
    this.totalsample_qty=this.totalchemical_qty*1+this.totalmicro_qty*1;
    this.control_sample= this.totalsample_qty*1*3;
    this.companies[Object.keys(this.companies).length] = temp;
    data.resetForm();
  }



}
