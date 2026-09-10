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
  grades;
  materials;
 
  grade = '';
  isNew = true;
  sample_qty=0;
  tests;
  subtests;
  units;
  unit = '';
  composite = 0;
  micro_qty=0;
  isYes=false;
  isNo=false;
  selectedProd = [];
  sample_unit='';
  material_subtype = 'API';
  material_code = '';
  chemical_name = '';
  selectedqty=0;
  selectMicroQty=0;
  companies = [];
  subtest = '';
  revisionList = [];
  refers;
  selectedRef=[];
  release_stability='';
  test_type = '';
  selectedTest;
  selectedSubTest;
  revisionDataList=[];
  specification='';
  isdesc=false;
  selectedFile: File;
  isupload = false;
  unit1='';
  isNewLocation = false;

  limit = '';

  isStorage=false;
  storage_condition='';
  storages;
  molicular_unit='';

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


  descr = '';
  lower_limit = 0;
  upper_limit = 0;
  
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getUnits();
  }
 


  getTests(value) {
    this.selectedTest = '';
    this.selectedSubTest = '';
    this.getspecSubTests = [];
    this.service.get('common.php?type=getTests&test_type='+ value).subscribe(response => {
      this.tests = Array.isArray(response) ? response : [];
    });
  }


  getspecSubTests = [];
  getSubtest(value) {
    this.selectedSubTest = '';
    this.getspecSubTests = [];
    if (!value || !Array.isArray(this.tests)) {
      return;
    }
    // Same source as /master/test — nested subtests from common.php?type=getTests
    const match = this.tests.find((t: any) => t && t.test === value);
    this.getspecSubTests = match && Array.isArray(match.subtests) ? match.subtests : [];
  }







  checkSpec(value) {
    if (value == 'New') {
      this.isNew = true;
      let date = this.formatDate(new Date());
      this.revisionList[this.revisionList.length] = {"spec_no": "AUTO GENERATE", "ver_no": "00", "change_mode": "NA", "change_reason": "NA", "effective_date": date};
    } else {
      this.isNew = false;
      this.revisionList = [];
    }
  }
 
   

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

 
 
 

  addTests(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (!this.selectedTest) {
      alertify.error('Please select Test');
      return;
    }
    let temp = data.value;
    let temp1 ={};
    if (temp['limit'] == "Range") {
      temp1['limits'] =  temp["lower_limit"] + temp["unit"] + " to " + temp["upper_limit"] + temp["unit"];
    } else if (temp['limit'] == "LessThan") {
      temp1['limits'] =  "NMT " + temp["lessthan"] + temp["unit"];
    } else if (temp['limit'] == "MoreThan") {
      temp1['limits'] =  "NLT " + temp["morethan"] + temp["unit"];
    } else if (temp["limit"] == "Compliances") {
      temp1["limits"] = "complies";
    }else if (temp['limit'] == "Not MoreThan") {
      temp1['limits'] = "NMT " + temp["lower_limit"] + temp["unit"];
    } else if (temp['limit'] == "Not LessThan") {
      temp1['limits'] = "NLT " + temp["upper_limit"] + temp["unit"];
    } else if (temp['limit'] == "Description") {
      temp1['limits'] = this.descr || temp['descr'] || '';
    }
  
    temp1['subtest'] = this.selectedSubTest || '';
    temp1['unit'] = this.unit;
     temp1['test_type'] = this.test_type;
    temp1['test'] = this.selectedTest;
    temp1['limit'] = this.limit;
    temp1['lower_limit'] = this.lower_limit;
    temp1['upper_limit'] = this.upper_limit;
    temp1['descr'] = this.descr || '';
    temp1['description'] = this.descr || '';
  
 
   
    this.companies[Object.keys(this.companies).length] = temp1;

    this.selectedTest = '';
    this.selectedSubTest = '';
    this.getspecSubTests = [];
    this.descr = '';
    this.lower_limit = 0;
    this.upper_limit = 0;
    this.limit = '';
    this.unit = '';
    data.resetForm();
    this.test_type = temp1['test_type'];
 
  }

  addRevision(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.revisionList[Object.keys(this.revisionList).length] = temp;
    data.resetForm();
   
  }

  deleteProduct(index) {
   
       this.companies.splice(index, 1);
    
  }

  deleteRevision(index) {
    this.revisionList.splice(index, 1);
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
 
  addMaterial(data) {
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
    // uploadData.append('material_code', this.selectedProd['material_code']);
    // uploadData.append('material_type', this.material_subtype);
    // uploadData.append('tests', JSON.stringify(this.companies));
    // uploadData.append('revisionHistory', JSON.stringify(this.revisionList));

    temp['material_code'] = this.selectedProd['material_code'];
    temp['tests'] = this.companies
    temp['clientlist'] = this.clientList;
    temp['revisionHistory'] = this.revisionList;
   

    console.log(temp);

    this.service.post('qc/specification/water.php?type=saveSpecification', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success("Data saved Successfully");
        this.router.navigate(['/master/specification/water']);
      } else { 
        alertify.error("Failed, An error occured, please try again!");
      }
    });
  }






































































  // onFilechange(event){
  //   if (event.target.files.length > 0) {
  //     this.selectedFile = event.target.files[0];
  //     this.isupload = true;
  //   } else {
  //     this.isupload = false;
  //   }
  // }
  
  // newUnit(value) {
  //   if (value == 'ADD NEW') {
  //     this.unit = this.unit1;
  //     this.isNewLocation = true;
  //   } else {
  //     this.isNewLocation = false;
  //   }
  // }

  // saveUnit(data) {
  //   if (!data.valid) {
  //     alertify.error('All fields are required!');
  //     return;
  //   }
  //   this.unit = this.unit1;
  //   this.service.post('qa/unit.php?type=saveUnit', JSON.stringify(data.value)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       this.unit1 = '';
  //       this.isNewLocation = false;
  //       this.getUnits();
  //       alertify.success("Data Save Successfully")
  //     } else {
  //       alertify.error('Failed: An error occured, Please try again!');
  //     }
  //   });
  // }

  // getStorage() {
  //   this.service.get('qa/master.php?type=getStorageConditions').subscribe(response => {
  //     this.storages = response;
  //   });
  // }

  // newStorage(value) {
  //   if (value == 'ADD NEW') {
  //     this.storage_condition = '';
  //     this.isStorage = true;
  //   }
  // }



  // addClient(data) {
 
  //   let temp = data.value;
  //   if (temp['limit'] == "Range") {
  //     temp['limits'] =  temp["lower_limit"] + temp["unit"] + " to " + temp["upper_limit"] + temp["unit"];
  //   } else if (temp['limit'] == "LessThan") {
  //     temp['limits'] =  "NMT " + temp["lessthan"] + temp["unit"];
  //   } else if (temp['limit'] == "MoreThan") {
  //     temp['limits'] =  "NLT " + temp["morethan"] + temp["unit"];
  //   } else if (temp["limit"] == "Compliances") {
  //     temp["limits"] = "complies";
  //   }
  //   temp['unit'] = this.unit;
  //   temp['sample_unit']=this.sample_unit;
  //   if(this.isYes==true){
  //     this.micro_qty += parseFloat(temp['sample_qty']);
  //   }
  //   if(this.isNo==true){
  //     this.composite += parseFloat(temp['sample_qty']);
  //   }
 
  //   this.clientList[Object.keys(this.clientList).length] = temp;
  //   temp['TrdNm']=this.selectedClient['TrdNm'];
  //   console.log(temp);
  //   data.resetForm();
  //   // // const element1 = document.getElementById('test') as HTMLElement;
  //   // element1.focus();
  // }
  // deleteClient(index) {
  //   if (this.clientList[index].test_type == 'Microbiology') {
  //     this.micro_qty = this.micro_qty - parseFloat(this.clientList[index].sample_qty);
  //     this.clientList.splice(index, 1);
  //   } else if(this.clientList[index].test_type == 'Chemical') {
  //     this.composite = this.composite - parseFloat(this.clientList[index].sample_qty);
  //     this.clientList.splice(index, 1);
  //   }
  // }
  // saveStorage() {
  //   if (this.storage_condition.length !== 0) {
  //     this.service.get('qa/master.php?type=saveStorageCondition&storage_condition=' + this.storage_condition).subscribe(response => {
  //       if (response['status'] == 'success') {
  //       //  this.getStorage();
  //         this.isStorage = false;
  //         this.storage_condition = '';
  //         alertify.success('Storage life saved successfully');
  //       } else {
  //         alertify.error('Failed: An error occured');
  //       }
  //     });
  //   }
  // }
  // getSample() {
  //   this.service.get('master/master.php?type=getSamplingPlans').subscribe(response => {
  //     this.samples = response;
  //   });
  // }

  // selectedSamplingPlan: any[];
  // filteredSamplingPlan: any[];
  // filterSampling(event) {
  //   //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
  //   let filtered =[];
  //   let query = event.query;
  //   for (let i = 0; i < this.samples.length; i++) {
  //     let country = this.samples[i];
  //     if (country.sampling_plan.toLowerCase().indexOf(query.toLowerCase()) == 0) {
  //       filtered[filtered.length] = country;
  //     }
  //   }

  //   this.filteredSamplingPlan = filtered;
  // }

  // getSampleDrawn(){
  //   this.service.get('master/master.php?type=getSampleDrawnFroms').subscribe(response => {
  //     this.sample_drawns = response;
  //   });
  // }

  // selectedSamplingDrawn: any[];
  // filteredSamplingDrawn: any[];
  // filteredSamplingD(event) {
  //   //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
  //   let filtered =[];
  //   let query = event.query;
  //   for (let i = 0; i < this.sample_drawns.length; i++) {
  //     let country = this.sample_drawns[i];
  //     if (country.sample_drawn_from.toLowerCase().indexOf(query.toLowerCase()) == 0) {
  //       filtered[filtered.length] = country;
  //     }
  //   }
  //   this.filteredSamplingDrawn = filtered;
  // }


  // getSampleWithD(){
  //   this.service.get('master/master.php?type=getSampleDrawnWiths').subscribe(response => {
  //     this.sample_withs = response;
  //   });
  // }

  // selectedSamplingWith: any[];
  // filteredSamplingWith: any[];
  // filteredSamplingW(event) {
  //   //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
  //   let filtered =[];
  //   let query = event.query;
  //   for (let i = 0; i < this.sample_drawns.length; i++) {
  //     let country = this.sample_withs[i];
  //     if (country.sample_drawn_with.toLowerCase().indexOf(query.toLowerCase()) == 0) {
  //       filtered[filtered.length] = country;
  //     }
  //   }
  //   this.filteredSamplingWith = filtered;
  // }

  // getHazards(){
  //   this.service.get('master/master.php?type=getPrecautions').subscribe(response => {
  //     this.hazards = response;
  //   });
  // }

  // selectedHazards: any[];
  // filteredHazards: any[];
  // filteredH(event) {
  //   //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
  //   let filtered =[];
  //   let query = event.query;
  //   for (let i = 0; i < this.hazards.length; i++) {
  //     let country = this.hazards[i];
  //     if (country.precautions.toLowerCase().indexOf(query.toLowerCase()) == 0) {
  //       filtered[filtered.length] = country;
  //     }
  //   }
  //   this.filteredHazards = filtered;
  // }



    // getClients(){
  //   this.service.get('common.php?type=getClients').subscribe(response => {
  //     this.clients = response;
  //   });
  // }


  // getClientName(index){
  //   index =index-1;
  //   if(index!==-1){
  //     this.selectedClient=this.clients[index];
  //   }

  // }


  
  // getGrades() {
  //   this.service.get('common.php?type=getGrades').subscribe(response => {
  //     this.grades = response;
  //   });
  // }

  // getMaterials(value) {
  //   this.service.get('qc/specification/raw.php?type=getMaterials&material_subtype=' + this.material_subtype + '&grade=' + this.grade).subscribe(response => {
  //     this.materials = response;
  //   });
  // }



}
