import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-reanalysis',
  templateUrl: './reanalysis.component.html',
  styleUrls: ['./reanalysis.component.css']
})
export class ReanalysisComponent implements OnInit {
  

  isInit = true;
  specifications;
  selectedTesting = [];
  ooschecklist_data;
  QCEmployee;
  selectedSpecification;
  tests;
  isapprove = false;

  isObservation = false;
  isOOS = false;
  selectedTest = [];
  form_type = "error1";
  start_time_new ;
  end_time_new ;
  start_time_new1 ;
  end_time_new1 ;
  description_oos;
  selectedIndex =0;

  api = 'pyc83d69ldr5hmcctu5rdnz66b498vz3nf5wofapfe87o13a';





  
  init = ({
    height: 300,
    menubar: true,
    statusbar: false,
    content_style: 'body { font-size: 12pt; font-family: Times; } ol{counter-reset: item}ol > li{display: block}ol > li:before {content: counters(item, ".") ". ";counter-increment: item}',
    plugins: [
      'advlist autolink lists link image charmap print preview anchor table'
    ],
    toolbar:
      'formatselect | bold italic backcolor | \
      alignleft aligncenter alignright alignjustify | \
      bullist numlist outdent indent | removeformat | table',
  
      
      setup: (editor) => {
      
        editor.on('init', () => {
          setTableWidth(editor);
        });
      
       
        editor.on('ExecCommand', (event) => {
          const command = event.command;
      
          if (command === 'mceInsertTable') {
            setTableWidth(editor);
          }
        });
      
        function setTableWidth(editor) {
         
          const tables = editor.dom.select('table');
          tables.forEach(table => {
            editor.dom.setStyle(table, 'width', '800px');
          });
        }
      }
      
  });
    emp_id: string;
    isDIGI: boolean=false
    isbutton: boolean=true
  
  




  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTesting();
    // this.getooschecklist();
    this.QcEmployee();


    // if('master1' != localStorage.getItem('department')){
    //   this.service.logout();
    // }



  }

  getPendingTesting() {
    this.service.get('qc/testing/raw.php?type=getRejectedTestingReport_Reanalysis&material_type=Raw Material&is_RDS=1').subscribe(response => {
      this.specifications = response;
    });
  }

  // getooschecklist() {
  //   this.ooschecklist_data =[];
  //   this.service.get('master/checklist.php?type=getooschecklist').subscribe(response => {
  //     this.ooschecklist_data = response;
  //   });
  // }

  QcEmployee() {
    
    this.service.get('master/checklist.php?type=getapprovedEmployee&department1=Quality Control').subscribe(response => {
      this.QCEmployee = response;
    });
  }

  isdeffbtn = false;

  furtherdiss(mainIndex, subIndex, value){
 
      if(value == 'NO'){
        this.isdeffbtn = true;
        
        try {
            if (this.ooschecklist_data[mainIndex] && Array.isArray(this.ooschecklist_data[mainIndex].check_points)) {
              this.ooschecklist_data[mainIndex].check_points = this.ooschecklist_data[mainIndex].check_points.slice(0, subIndex);
            } else {
              throw new Error("Invalid mainIndex or check_points is not an array.");
            }
            if (mainIndex >= 0 && mainIndex < this.ooschecklist_data.length) {
              mainIndex = mainIndex + 1;
              this.ooschecklist_data = this.ooschecklist_data.slice(0, mainIndex);
            } else {
              throw new Error("Invalid mainIndex.");
            }
        } catch (error) {
            console.error("An error occurred:", error);
        }
      }else{
        
      }
  
    console.log( this.ooschecklist_data);

  }
  
  balance_code;
  laf_id;
  viewSpecification(index) {
    this.selectedIndex = index;
    this.selectedTesting = this.specifications[index];

    
  }

  showinciForm(index) {
    this.selectedTest = this.tests[index];
    this.isObservation = true;
  }

 
  previous_add_observation;
 

 
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
        this.Approverejecttest()
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }


  Approverejecttest() {
     
    console.log(this.selectedTesting['testing_no']);

    this.service.post('qc/testing/raw.php?type=Approverejecttest&testing_no='+this.selectedTesting['testing_no'], JSON.stringify(this.selectedTest)).subscribe(response => {
      if (response['status'] == "success") {
        this.isObservation = false;
        this.isInit = true;
        alertify.success(this.form_type + ' send for Approval.');
        this.isbutton=true
        this.getPendingTesting();
      } else {
        alertify.error('An error occured');
      }
    });
  }
 
 

}