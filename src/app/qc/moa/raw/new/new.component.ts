import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;
  specifications;

  selectedSpec = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getPendingRawMOA();
  }
  
  getPendingRawMOA() {
    this.service.get('qc/method.php?type=getPendingSpecForMoa&matType=Raw Material').subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
      
const tests = this.selectedSpec['test'];

  // Loop through all tests
  for (let i = 0; i < tests.length; i++) {
    const testItem = tests[i];

    // Loop through all methodsData for the test
    for (let j = 0; j < testItem['methodsData'].length; j++) {
      const md = testItem['methodsData'][j];

      // Initialize HPLC arrays if they are null
      md['instrumentParameterList'] = md['hplc']?.instrumentParameterList || [];
      md['refractiveIndexList'] = md['hplc']?.refractiveIndexList || [];
      md['methodParameterList'] = md['hplc']?.methodParameterList || [];
      md['retentionTimeList'] = md['hplc']?.retentionTimeList || [];
      md['cromatograms_list'] = md['hplc']?.cromatograms_list || [];
    }

    // Check HPLC for this test
    let HPLC = false;
    for (let j = 0; j < testItem['methodsData'].length; j++) {
      const md = testItem['methodsData'][j];
      if (
        md['instrumentParameterList'].length > 0 ||
        md['refractiveIndexList'].length > 0 ||
        md['methodParameterList'].length > 0 ||
        md['retentionTimeList'].length > 0 ||
        md['cromatograms_list'].length > 0 ||
        testItem['phases']?.length > 0
      ) {
        HPLC = true;
        break; // No need to check further if one method has HPLC data
      }
    }

    // Add HPLC flag to the test
    testItem['HPLC'] = HPLC;
  }

  console.log(this.selectedSpec['test']);
      
 
  }

  addmethod(id) {
    this.router.navigate(['/qc/moa/methods/new/' + id]);
  }

  viewMethod(id) {
    this.router.navigate(['/qc/moa/methods/news/' + id]);
  }


  selectedrivision=[]
  doc_name
  method_no
  
  isRivisionrequest=false;
  Rivision(index,test){
    this.isRivisionrequest=true;
    this.selectedrivision=this.specifications[index]
    this.doc_name=test;
    this.method_no=this.selectedrivision['specification_no']
    
  }
  save(data){
    let temp=data.value;
  
  
    this.service.post('master/test.php?type=save_Request&method_no='+this.selectedrivision['test_method_no'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('save');
        this.isRivisionrequest=false;
        // this.router.navigate(['/master/test']);
      } else {
        alertify.error('An error Occured, Please try again!');
      }
    });
  }

isPrepare = false;

PrepareGTP() {
  this.isPrepare = true;


}

closeModal() {
  this.isPrepare = false;

}



// Object to store toggle states for each test/method
toggleStates: any = {};

toggleShow(section: string, testIndex: number, methodIndex: number) {
  const key = `${section}_${testIndex}_${methodIndex}`;
  this.toggleStates[key] = !this.toggleStates[key];
}

isShown(section: string, testIndex: number, methodIndex: number): boolean {
  const key = `${section}_${testIndex}_${methodIndex}`;
  return this.toggleStates[key] || false;
}

// Section visibility for eye buttons (used by template)
isShown1 = false; isShown2 = false; isShown3 = false; isShown4 = false; isShown5 = false;
isShown6 = false; isShown7 = false; isShown8 = false; isShown9 = false; isShown10 = false;
isShown11 = false; isShown12 = false; isShown13 = false; isShown14 = false; isShown15 = false;

toggleShow1(): void { this.isShown1 = !this.isShown1; }
toggleShow2(): void { this.isShown2 = !this.isShown2; }
toggleShow3(): void { this.isShown3 = !this.isShown3; }
toggleShow4(): void { this.isShown4 = !this.isShown4; }
toggleShow5(): void { this.isShown5 = !this.isShown5; }
toggleShow6(): void { this.isShown6 = !this.isShown6; }
toggleShow7(): void { this.isShown7 = !this.isShown7; }
toggleShow8(): void { this.isShown8 = !this.isShown8; }
toggleShow9(): void { this.isShown9 = !this.isShown9; }
toggleShow10(): void { this.isShown10 = !this.isShown10; }
toggleShow11(): void { this.isShown11 = !this.isShown11; }
toggleShow12(): void { this.isShown12 = !this.isShown12; }
toggleShow13(): void { this.isShown13 = !this.isShown13; }
toggleShow14(): void { this.isShown14 = !this.isShown14; }
toggleShow15(): void { this.isShown15 = !this.isShown15; }


}
