import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-test',
  templateUrl: './test.component.html',
  styleUrls: ['./test.component.css']
})
export class TestComponent implements OnInit {

  isView = false;
  isTest = false;
  isStart = false;
  results;
  analysis_start_time = '';
  analysis_end_time = '';

  selectedResult = [];
  selectedTesting = [];
  tests = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    // this.getPendingTestings();
  }
  getData(value){
    if(value=='Production'){
      this.getPendingTestingsProd();
    }
    else if(value=='Packing'){
      this.getPendingTestings();
    }
  }
  getPendingTestings(){
    this.service.get('production/technical.php?type=getPendingTestings').subscribe(response => {
      this.results = response;
    });
  }
  getPendingTestingsProd(){
    this.service.get('production/technical.php?type=getPendingTestingsProd').subscribe(response => {
      this.results = response;
    });
  }

  jadugarIndex;

  view(index){
    this.selectedResult = this.results[index];
    this.tests = this.selectedResult['tests']
    this.isView = true;
    this.jadugarIndex = index;
  }



  viewTest(index){
    this.selectedTesting = this.tests[index];
    this.isView = true;
    this.isTest = true;

    if(this.selectedTesting['method_details'] == 1){
      this.getTesting_methods(this.selectedTesting['test_method_no']);
    }
 
    this.updateSamplingTime('start');
     
 
  }



  method;
  selectedMethod
  getTesting_methods(test_method_no){
    this.service.get('qc/testing/raw.php?type=getTesting_methods&test_method_no='+test_method_no).subscribe(response => {
      this.method = response;
      this.selectedMethod=this.method[0];
      console.log(this.selectedMethod['eqdates'])
    });
  } 


  start_time;
  end_times;
  
  getCurrentTime1(action: string, data: any, i: number) {
    var d = new Date(),
        h = (d.getHours() < 10 ? '0' : '') + d.getHours(),
        m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();

    if (action == 'from_time') {
        data.start_time = h + ':' + m;
    } else {
        data.end_times = h + ':' + m;
    }
}



selectedFile: File | null = null; // Variable to store the selected file

handleFileInput(event: any): void {
  this.selectedFile = event.target.files[0]; // Retrieve the selected file
}


weighing_table:any=[];
g_weight = 0;
t_weight = 0;
n_weight = 0;

addweighingDetails(data){

  if (!data.valid) {
    alertify.error('Please Enter required Field');
    return;
  }

  let temp = data.value;


  if (this.selectedFile) {

    const reader = new FileReader();
    reader.onload = () => {
    temp.file_upload = reader.result;

    };
    reader.readAsDataURL(this.selectedFile);
  }

 
  this.weighing_table.push(temp);
  data.reset();
  console.log(this.weighing_table);


   
      this.g_weight = 0;
      this.t_weight = 0;
      this.n_weight = 0;
 
}


isdata6: boolean = false;
isdata7: boolean = false;
isdata8: boolean = false;

delWeighjing(index){
  this.weighing_table.splice(index ,1);
}

dataShow6() {
  this.isdata6 = !this.isdata6;
}

dataShow7 () {
  this.isdata7 = !this.isdata7;
}
dataShow8 () {
  this.isdata8 = !this.isdata8;
}

viewPhoto(file) {
  window.open(this.service.url + '../../upload/test_calculation/' + file);
   //window.open(url, '_blank');
}


rows = [
  { sample: '(Wc)', oven_used: '',balance: '',datalogger: '', startTime: '', stopTime: '', dryingTime: 0, duplicate1: 0, duplicate2: 0 },
  { sample: '(Ws)', oven_used: '',balance: '',datalogger: '', startTime: '', stopTime: '', dryingTime: 0, duplicate1: 0, duplicate2: 0 },
 ];


wdrows = [
    { sample: '(Wd)', oven_used: '',balance: '',datalogger: '', startTime: '', stopTime: '', dryingTime: 0, duplicate1: 0, duplicate2: 0 }
];

// wdRow.lod = ((ws - wd) * 100) / (ws - wc);




average1 =0;
average2 =0;
finalLod =0;

calculateLOD(): void {
  const wcduplicate1 = this.rows[0].duplicate1;
  const wcduplicate2 = this.rows[0].duplicate2;

  const wsduplicate1 = this.rows[1].duplicate1;
  const wsduplicate2 = this.rows[1].duplicate2;

  let wdduplicate1 = 0;
  let wdduplicate2 = 0;

  let length = this.wdrows.length;

  wdduplicate1 = this.wdrows.reduce((acc, add) => acc + Number(add.duplicate1 || 0), 0);
  wdduplicate2 = this.wdrows.reduce((acc, add) => acc + Number(add.duplicate2 || 0), 0);

  const wd1 = Number((wdduplicate1 / length).toFixed(4));
  const wd2 = Number((wdduplicate2 / length).toFixed(4));

  this.average1 = Number((((wsduplicate1 - wd1) * 100) / (wsduplicate1 - wcduplicate1)).toFixed(4));
  this.average2 = Number((((wsduplicate2 - wd2) * 100) / (wsduplicate2 - wcduplicate2)).toFixed(4));

  this.finalLod = Number(((this.average1 + this.average2) / 2).toFixed(4));
}

 
calculateDryingTime(index: number): void {
  const row = this.wdrows[index];
  if (row.startTime && row.stopTime) {
    const startTime = new Date(row.startTime).getTime();
    const stopTime = new Date(row.stopTime).getTime();
    row.dryingTime = (stopTime - startTime) / (1000 * 60);

    if(row.dryingTime > 240){
      alertify.error('Processed Time Is Exceeded');

    }else if(row.dryingTime < 240){
      alertify.error('Processed Time Is Not Completed');
    }


  }
}

addWdRow(): void {
  this.wdrows.push({
    sample: '(Wd)',
    oven_used: '',
    balance: '',
    datalogger: '',
    startTime: '',
    stopTime: '',
    dryingTime: 0,
    duplicate1: 0,
    duplicate2: 0,
    // lod: 0
  });
}

delWdRow(): void {
  this.wdrows.pop();
}

reportResult(index){
  this.result =0;
  this.result = this.finalLod;
  this.check_stat();
 
  const  result = [
    { sample: '% LOD ( See Calculation Below)',  average1: this.average1, average2: this.average2 },
    { sample: 'Average % LOD',  finalLod: this.finalLod }
  ];

  const mergedArray = [...this.rows, ...this.wdrows , ...result];
 
  console.log(mergedArray);
 
  this.selectedMethod['calculations'][index]['calc'] = mergedArray;
 
  console.log(this.selectedMethod['calculations']);
 
}


result=0;
  result0=0;
  result1=0;
  observation=''
  result3=''
  result4;
  dis_qty_chem;


  check_stat(){
    console.log("jadugar");
    

    if(this.selectedTesting['limit_type']=='Range'){
     
         if(this.result  >= Number(this.selectedTesting['lower_limit'])  && this.result  <=  Number(this.selectedTesting['upper_limit']) ){
        this.observation='complies';
        console.log('PASS.');
      }else{
        this.observation='non-complies';
        console.log('Fail.');
      }

    }
    else if(this.selectedTesting['limit_type']=='Not LessThan'){

      if(this.result  >= Number(this.selectedTesting['lower_limit']) ){
        this.observation='complies';
        console.log('PASS' + this.selectedTesting['lower_limit']);
      }else{
        this.observation='non-complies';
        console.log('Fail' +this.selectedTesting['lower_limit']);
      }

    }
    else if(this.selectedTesting['limit_type']=='Not MoreThan'){

      if(this.result  <=  Number(this.selectedTesting['upper_limit']) ){
        this.observation='complies';
        console.log('PASS' +this.selectedTesting['upper_limit']);
      }else{
        this.observation='non-complies';
        console.log('Fail'+ this.selectedTesting['upper_limit']);
      }

    }
    else if(this.selectedTesting['limit_type']=='Description' || this.selectedTesting['limit_type']=='Compliances'){
    
        this.observation=this.observation;
    }
  }


  start_date;
  end_time;
  end_date;
  hplc_len;
  updateSamplingTime(value) {
    this.hplc_len=0
 
    var d = new Date(),
    year = d.getFullYear(),
    month = ((d.getMonth() + 1) < 10 ? '0' : '') + (d.getMonth() + 1),
    day = (d.getDate() < 10 ? '0' : '') + d.getDate(),
    h = (d.getHours() < 10 ? '0' : '') + d.getHours(),
    m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();


    if (value == 'start') {

      this.start_date = day + '-' + month + '-' + year + ' ' + h + ':' + m;
      

    } else if (value == 'end') {

      this.end_time = h + ':' + m;
      this.end_date = day + '-' + month + '-' + year + ' ' + h + ':' + m;
       
    }


    let aa = this.selectedMethod['hplc']?.['Instrument_parameter_list']?.length || 0;
    let bb = this.selectedMethod['hplc']?.['Refractive_indexForm_list']?.length || 0;
    let cc = this.selectedMethod['hplc']?.['Method_ParameterForm_list']?.length || 0;
    let dd = this.selectedMethod['hplc']?.['Retention_time_list']?.length || 0;
    let sum=aa+bb+cc+dd;
    console.log(sum)
    this.hplc_len=sum;

  }



viewFile(file: any): void {
  if (file) {
    if (file.startsWith('data:application/pdf')) {
      const newWindow = window.open();
      if (newWindow) {
        newWindow.document.write('<iframe width="100%" height="100%" src="' + file + '"></iframe>');
      } else {
        alert('Pop-up window blocked. Please allow pop-ups and try again.');
      }
    } else {
      alert('Unsupported file format. Cannot view.');
    }
  } else {
    alert('File data not found.');
  }
}


  getCurrentTime(value){
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    if(value == 'start'){
      this.analysis_start_time = h + ':' + m;
      this.analysis_start_time = new Date().toLocaleTimeString();;
      this.isStart = true;
    }if(value == 'stop'){
      this.analysis_end_time = h + ':' + m;
      this.analysis_end_time = new Date().toLocaleTimeString();;
    }
  }
  from_rec='';
  submitTest(data) {

    console.log(data);
    if(!data.valid){
      alertify.error('All Field are required');
      return;
    }

    const uploadData = new FormData();
    
 
     uploadData.append('start_time', this.start_date)
      uploadData.append('observation', this.observation)
     uploadData.append('remark', this.remark)
    uploadData.append('end_time', this.end_date)
    uploadData.append('observation', this.observation)
    uploadData.append('weighing_table', this.weighing_table)
    uploadData.append('testing_test_id',this.selectedTesting['ttt_id'])
    uploadData.append('test_method_no', this.selectedTesting['test_method_no'])
    uploadData.append('testing_no', this.selectedTesting['testing_no'])
    uploadData.append('material_code', this.selectedTesting['material_code'])
    uploadData.append('material_name', this.selectedTesting['material_name'])
    uploadData.append('ar_no', this.selectedTesting['ar_no'])
    uploadData.append('from_rec', this.from_rec)
    uploadData.append('Tech_info_id', this.selectedResult['id'])

 
    this.service.post('production/technical.php?type=saveTestingForm12&id='+this.selectedTesting['ttt_id']+'&result='+this.result
      +'&ti_no='+this.selectedResult['ti_no'] , uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        // this.getPendingTestings();
        
        this.isView = false; 
        this.isTest = false;
        this.isStart = false;
        this.result = 0;
        this.observation = '';

         alertify.success('test successfully send for approval');
         this.results=[];

        setTimeout(() => {
          this.view(this.jadugarIndex);
        }, 1000);

      } else {
        alertify.error('An error occured, please try again');
      }
    });
  


  }


  remark;
 submitTest1(data) {
    console.log(data);
    if(!data.valid){
      alertify.error('All Field are required');
      return;
    }

    const uploadData = new FormData();
    
 
     uploadData.append('start_time', this.start_date)
      uploadData.append('observation', this.observation)
     uploadData.append('remark', this.remark)
    uploadData.append('end_time', this.end_date)
    uploadData.append('observation', this.observation)
    uploadData.append('chemi_data',this.selectedMethod['chems_dats'])
    uploadData.append('dilutions',this.selectedMethod['dilutions'])
    uploadData.append('eqdates',this.selectedMethod['eqdates'])
    uploadData.append('Genral_Instruction',this.selectedMethod['Genral_Instruction'])
    uploadData.append('purpose',this.selectedMethod['purpose'])
    uploadData.append('Scope',this.selectedMethod['Scope'])
    uploadData.append('Associative_Document',this.selectedMethod['Associative_Document'])
    uploadData.append('Refrenced_Document',this.selectedMethod['Refrenced_Document'])
    uploadData.append('defination',this.selectedMethod['defination'])
    uploadData.append('testinginstruction',this.selectedMethod['testinginstruction'])
    uploadData.append('Safety',this.selectedMethod['Safety'])
    uploadData.append('procedure',this.selectedMethod['procedure'])
    uploadData.append('balance',this.selectedMethod['balance1'])
    uploadData.append('weighing_table', this.weighing_table)
    uploadData.append('glasswares',this.selectedMethod['glasswares'])
    uploadData.append('volumetric_solutions',this.selectedMethod['volumetric_solutions'])
    uploadData.append('hplc',this.selectedMethod['hplc'])
    uploadData.append('calculations',this.selectedMethod['calculations'])
    uploadData.append('Documentations',this.selectedMethod['Documentations'])
    uploadData.append('Trendings',this.selectedMethod['Trendings'])
    uploadData.append('testing_test_id',this.selectedTesting['ttt_id'])
    uploadData.append('test_method_no', this.selectedTesting['test_method_no'])
    uploadData.append('testing_no', this.selectedTesting['testing_no'])
    uploadData.append('material_code', this.selectedTesting['material_code'])
    uploadData.append('material_name', this.selectedTesting['material_name'])
    uploadData.append('ar_no', this.selectedTesting['ar_no'])

 
    this.service.post('production/technical.php?type=saveTestingForm1&id='+this.selectedTesting['ttt_id']+'&result='+this.result
      +'&ti_no='+this.selectedResult['ti_no'] , uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        // this.getPendingTestings();
        
        this.isView = false; 
        this.isTest = false;
        this.isStart = false;
        this.result = 0;
        this.observation = '';

         alertify.success('test successfully send for approval');

        setTimeout(() => {
          this.view(this.jadugarIndex);
        }, 1000);

      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }


  isDIGI =false;
  isbutton =true;
  emp_id = '';

  openDigiSign(){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
        this.approve()
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }


approve(){

  this.service.post('production/technical.php?type=saveTestingawaitForm&testing_no='+this.selectedResult['testing_no'], JSON.stringify(this.selectedTesting)).subscribe(response => {
    if (response['status'] == 'success') {
      // this.getPendingTestings();
     
      alertify.success('test successfully send for approval');
      this.isbutton=true;
      this.isView=false;
      this.results=[];
    } else {
      alertify.error('An error occured, please try again');
    }
  });

}






}
