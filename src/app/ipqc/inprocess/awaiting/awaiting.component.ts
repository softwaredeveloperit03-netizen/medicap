import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css'],
  providers:[DatePipe]
})
export class AwaitingComponent implements OnInit {
  results;
  from_date='';
  to_date='';
  isview=false;
  selectedResult=[];
  remark='Complies';
  selectedFile:File;
  isAttachment=0;

 constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');     
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getTechnicalLog();
  }

  getTechnicalLog(){
    this.service.get('production/technical.php?type=getPendingTechnicals').subscribe(response=>{
      this.results=response;
    });
  }


  view(index){
    this.selectedResult=this.results[index];
    this.isview=true;
  }

  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isAttachment = 1;
  }

  isTest = false;
  selectedTest = [];
  parameters = [];
  viewtest(index) {
    let tests = this.selectedResult['tests'];
    this.selectedTest = tests[index];
    if (this.selectedTest['calculation'] !== '') {
      this.parameters = this.selectedTest['parameters'].parameters;
    }
    this.isTest = true;
  }

  instruments = [];
  selectInstrument(index) {
    index = index - 1;
    if (index !== -1) {
      let instruments = this.selectedTest['instruments'];
      this.instruments = instruments[index].equipments;
    }
  }

  save(){
    const temp = this.selectedResult;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      // Use `key` and `value`
      uploadData.append(key, value);
    }

    let tests1 = [];
    let tests = this.selectedResult['tests'];
    for (let i = 0; i < tests.length; i++) {
      let test = tests[i];
      let test1 = {};
      test1["test"] = test["test"];
      test1["subtest"] = test["subtest"];
      test1["limits"] = test["limits"];
      test1["result"] = test["result"];
      tests1[tests1.length] = test1;
    }
  
    uploadData.append("remark", this.remark);
    uploadData.append("tests",JSON.stringify(tests1));
    if (this.selectedFile !== undefined) {
      uploadData.append('testattachment', this.selectedFile, this.selectedFile.name);
    }

    this.service.post('production/technical.php?type=saveTestingReport&id='+this.selectedResult['id'],uploadData).subscribe(response=>{ 
    if(response['status']=='success'){
        alertify.success('data save successfuly');
        this.getTechnicalLog();
        this.isview=false;
      }else{
        alertify.error('some error occured!please try again');
      }
    });
  }

  calculate() {
    if (this.selectedTest['calculation'] == 'Water Content') {
      this.selectedTest['formula_result'] = +parseFloat(((+this.parameters[0].value * +this.parameters[1].value * 100) / (+this.selectedResult['sample_qty'] * 1000 * 0.810)) + '').toFixed(2);
      this.selectedTest['result'] = this.selectedTest['formula_result'];
    } else if (this.selectedTest['calculation'] == 'Water Content Methanol') {
      this.selectedTest['formula_result'] = +parseFloat(((+this.parameters[0].value * +this.parameters[1].value * 100) / (+this.selectedResult['sample_qty'] * 1000 * 0.792)) + '').toFixed(2);
      this.selectedTest['result'] = this.selectedTest['formula_result'];
    } else if (this.selectedTest['calculation'] == '% Water Content') {
      this.selectedTest['formula_result'] = +parseFloat(((+this.parameters[0].value * +this.parameters[1].value * 100) / (+this.parameters[2].value * 1000)) + '').toFixed(2);
      this.selectedTest['result'] = this.selectedTest['formula_result'];
    } else if (this.selectedTest['calculation'] == 'density') {
      this.selectedTest['formula_result'] = +parseFloat(((+this.parameters[2].value - +this.parameters[0].value) / (+this.parameters[1].value - +this.parameters[0].value)) + '').toFixed(3);
      this.selectedTest['result'] = this.selectedTest['formula_result'];
    }

    if (this.selectedTest['limit_type'] == 'LessThan') {
      if (this.selectedTest['result'] <= this.selectedTest['lessthan']) {
        this.selectedTest['remark'] = 'Complies';
      } else {
        this.selectedTest['remark'] = 'Not Complies';
      }
    } else if (this.selectedTest['limit_type'] == 'Range') {
      if (this.selectedTest['result'] >= this.selectedTest['lower_limit'] && this.selectedTest['result'] <= this.selectedTest['upper_limit']) {
        this.selectedTest['remark'] = 'Complies';
      } else {
        this.selectedTest['remark'] = 'Not Complies';
      }
    }
  } 
}
