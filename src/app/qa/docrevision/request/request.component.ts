import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;
@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {

  editorInit = {
    height: 550,
    menubar: false,
    statusbar: false,
    readonly: true
};

  
  api = 'pyc83d69ldr5hmcctu5rdnz66b498vz3nf5wofapfe87o13a';

  isdata: boolean;
  isdata0: boolean;
  dataShow0() {
    this.isdata0 = !this.isdata0;
  }
  dataShow() {
    this.isdata = !this.isdata;
  }
  isdata1: boolean;
  dataShow1() {
    this.isdata1 = !this.isdata1;
  }
  isdata2: boolean;
  dataShow2() {
    this.isdata2 = !this.isdata2;
  }
  isdata3: boolean;
  dataShow3() {
    this.isdata3 = !this.isdata3;
  }
  isdata4: boolean;
  dataShow4() {
    this.isdata4 = !this.isdata4;
  }
  isdata5: boolean;
  dataShow5() {
    this.isdata5 = !this.isdata5;
  }
  isdata6: boolean;
  dataShow6() {
    this.isdata6 = !this.isdata6;
  }
  isdata7: boolean;
  dataShow7() {
    this.isdata7 = !this.isdata7;
  }
  isdata8: boolean;
  dataShow8() {
    this.isdata8 = !this.isdata8;
  }
  isdata9: boolean;
  dataShow9() {
    this.isdata9 = !this.isdata9;
  }
  isdata10: boolean;
  dataShow10() {
    this.isdata10 = !this.isdata10;
  }
  isdata11: boolean;
  dataShow11() {
    this.isdata11 = !this.isdata11;
  }
  isdata12: boolean;
  dataShow12() {
    this.isdata12 = !this.isdata12;
    
    
  }
  isdata13: boolean;
  dataShow13() {
    this.isdata13 = !this.isdata13;
  }
  isdata14: boolean;
  dataShow14() {
    this.isdata14 = !this.isdata14;
  }
  constructor(private service:DataAccessService, private router: Router) { }
  isLog = true;
  isView = false;
  tests;
  method;
  results;
  methods: any = {};
  ngOnInit(): void {
    this.getTests();
  }
  test_type='';
  getTests() {
    this.service.get('qa/request.php?type=get_requests')
    .subscribe(response => {
      this.results = response;
      
    });
      

  }
  viewMethod(test_method_no){
    // this.selectedTest = this.tests[index];
    this.isLog=false;
    this.isView=true;
    // this.isLog=false;
    console.log('hi')
   
    this.getTesting_methods(test_method_no)
  }
  getTesting_methods(test_method_no){
    this.service.get('qc/testing/raw.php?type=getTesting_methods&test_method_no='+test_method_no).subscribe(response => {
      this.method = response;
      this.methods=this.method[0];
      console.log(this.methods['eqdates'])
    });
  } 
  Status(status){
    let temp={};

    this.service.post('qa/request.php?type=approve_REQUEST&status='+status+'&method_no='+this.methods['test_method_no'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success(status);
        // this.router.navigate(['/master/test']);
      } else {
        alertify.error('An error Occured, Please try again!');
      }
    });
  }
}
