import { Component, OnInit, SecurityContext } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DomSanitizer,SafeHtml } from '@angular/platform-browser';

declare let alertify;
@Component({
  selector: 'app-initiate-checking',
  templateUrl: './initiate-checking.component.html',
  styleUrls: ['./initiate-checking.component.css']
})
export class InitiateCheckingComponent implements OnInit {

  init = ({
    height: 300,
    menubar: true,
    readonly:true,
    statusbar: false,
    content_style: 'body { font-size: 12pt; font-family: Times; } ol{counter-reset: item}ol > li{display: block}ol > li:before {content: counters(item, ".") ". ";counter-increment: item}',
    plugins: [
      'advlist autolink lists link image charmap print preview anchor'
    ],
    toolbar:
      'formatselect | bold italic backcolor | \
      alignleft aligncenter alignright alignjustify | \
      bullist numlist outdent indent | removeformat '
  });
  api = '9boizsxuop2gshu192rv00t5xs8z7u7o6ohvpgswgy1qkida';




  // departments: { name: string; value: boolean; status: boolean }[]= [
  //   { name: 'Stores', "value": false,"status":false },
  //    { name: 'Client', "value": false,"status":false },
  //    { name: 'Human Resource', "value": false,"status":false },
  //   { name: 'Quality Control', "value": false,"status":false },
  //   { name: 'Account', "value": false,"status":false },
  //   { name: 'Security', "value": false,"status":false },
  //   { name: 'Purchase', "value": false,"status":false },
  //   { name: 'Quality Assurance', "value": false,"status":false },
  //   { name: 'Microbiology', "value": false,"status":false },
  //   { name: 'Engineering', "value": false,"status":false },
  //   { name: 'Store', "value": false,"status":false },
  //   { name: 'Packing', "value": false,"status":false },
  //   { name: 'R & D', "value": false,"status":false },
  //   { name: 'Marketing', "value": false,"status":false },
  //   { name: 'Vendor', "value": false,"status":false },
  //   { name: 'Management', "value": false,"status":false },
  //   { name: 'Planning', "value": false,"status":false },
  //   { name: 'Admin', "value": false,"status":false },
  //   { name: 'IPQA', "value": false,"status":false },
  //   { name: 'Regulatory', "value": false,"status":false },
  //   { name: 'EHS', "value": false,"status":false },
  //   { name: 'Enginering Store', "value": false,"status":false },
  //   { name: 'Production', "value": false,"status":false },
  //   { name: 'Business Development', "value": false,"status":false },
  //   { name: 'F & D', "value": false,"status":false },
  //   { name: 'IPQC', "value": false,"status":false },
  // ];
  // updateDept(checked: boolean, index: number): void {
  //   this.departments[index].status = checked;
  // }



  isView = false;
  results;


  selectedResult = [];
  remark = '';

  constructor(private service: DataAccessService, private sanitizer: DomSanitizer) { }

  ngOnInit(): void {

    this.getpendinginitiation();
    this.get_format();
  }
  formats;
  get_format() {
    this.service.get('sops1.php?type=get_format').subscribe(response => {
      this.formats = response;
    });
  }



  getpendinginitiation() {
    this.service.get('sops.php?type=getpendinginitiation').subscribe(response => {
      this.results = response;

    });
  }
  view_format:any;
  selectedformats=[];
  purposeArray=[];
  scopeArray=[];
  roleArray=[];
  definitionArray=[];
  referenceArray=[];
  processArray=[];
  proceduresArray=[];
  abbreviationsArray=[];
  trainingArray=[];
  distributionArray=[];
  attachmentsArray=[];
  revisionArray=[];
  purposetitle = 'Purpose';
  scopetitle = 'Scope';
  RolesRestitle = 'Role and Responsibility';
  definitiontitle = 'Definition';
  externaltitle = 'External References And Associated Documents';
  Processtitle = 'Process Overview';
  Proceduretitle = 'Procedure';
  Abbreviationtitle = 'Abbreviation';
  Trainingtitle = 'Training Requirement';
  Distributiontitle = 'Distribution';
  Attachmentstitle = 'Attachments';
  Revisiontitle = 'Revision History';
  abbrivations;
  roless;
  definitionss;
  referencess;
  
  add1_title;
  add2_title;
  add3_title;
  add4_title;
  add5_title;
  adds1Array=[];
  adds2Array=[];
  adds3Array=[];
  adds4Array=[];
  adds5Array=[];

  view(index) {
    this.selectedResult = this.results[index];
    
    this.abbrivations=this.selectedResult['abbreviation']
    this.roless=this.selectedResult['role']
    this.definitionss=this.selectedResult['definition']
    this.referencess=this.selectedResult['reference']
    this.isView = true;
    this.selectedformats=this.formats[0]
    this.purposeArray = JSON.parse(this.selectedformats['purpose']);
    this.scopeArray = JSON.parse(this.selectedformats['scope']);
    this.roleArray = JSON.parse(this.selectedformats['role']);
    this.definitionArray = JSON.parse(this.selectedformats['definition']);
    this.referenceArray = JSON.parse(this.selectedformats['reference']);
    this.processArray = JSON.parse(this.selectedformats['process']);
    this.proceduresArray = JSON.parse(this.selectedformats['procedures']);
    this.abbreviationsArray = JSON.parse(this.selectedformats['abbreviation']);
    this.trainingArray = JSON.parse(this.selectedformats['training']);
    this.distributionArray = JSON.parse(this.selectedformats['distribution']);
    this.attachmentsArray = JSON.parse(this.selectedformats['attachments']);
    this.revisionArray = JSON.parse(this.selectedformats['revision']);

    this.purposetitle = this.purposeArray[0]?.['purposetitle'];
    this.scopetitle = this.scopeArray[0]?.['scopetitle'];
    this.RolesRestitle = this.roleArray[0]?.['RolesRestitle'];
    this.definitiontitle = this.definitionArray[0]?.['definitiontitle'];
    this.externaltitle = this.referenceArray[0]?.['externaltitle'];
    this.Processtitle = this.processArray[0]?.['Processtitle'];
    this.Proceduretitle = this.proceduresArray[0]?.['Proceduretitle'];
    this.Abbreviationtitle = this.abbreviationsArray[0]?.['Abbreviationtitle'];
    this.Trainingtitle = this.trainingArray[0]?.['Trainingtitle'];
    this.Distributiontitle = this.distributionArray[0]?.['Distributiontitle'];
    this.Attachmentstitle = this.attachmentsArray[0]?.['Attachmentstitle'];
    this.Revisiontitle = this.revisionArray[0]?.['Revisiontitle'];
   
    this.adds1Array = JSON.parse(this.selectedformats['adds1']);
    this.adds2Array = JSON.parse(this.selectedformats['adds2']);
    this.adds3Array = JSON.parse(this.selectedformats['adds3']);
    this.adds4Array = JSON.parse(this.selectedformats['adds4']);
    this.adds5Array = JSON.parse(this.selectedformats['adds5']);
 this.add1_title = this.adds1Array[0]?.['add1_title'];
    this.add2_title = this.adds2Array[0]?.['add2_title'];
    this.add3_title = this.adds3Array[0]?.['add3_title'];
    this.add4_title = this.adds4Array[0]?.['add4_title'];
    this.add5_title = this.adds5Array[0]?.['add5_title'];
 

  }
  View_Process(url) {
    url = this.service.url + '../../upload/Sops/' + url;
    window.open(url, '_blank');
  }
  update(status) {
    let temp={};
    // let selectedDepartments = this.departments.filter(department => department['status']).map(department => department['name']);
    //   temp["selectedDepartments"] = selectedDepartments;
    this.service.post('sops.php?type=checkinitiation&status=' + status + '&id=' + this.selectedResult['id'] + '&remark=' + this.remark + '&sop_no=' + this.selectedResult['sop_no'],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        this.isView = false;
        this.remark = '';
        this.getpendinginitiation();
        alertify.success(this.service.t('common.updatedSuccess'));
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }
 
 
  

  

  Download(){
    this.service.open('sops.php?type=download_Sop&id='+this.selectedResult['id']);

  }











 
 



































savescopeForm(data){
  let temp = data.value;
 
 this.service.post('sops.php?type=saveScope&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });
 
}
saveadds1Form(data){
  let temp = data.value;
 
 this.service.post('sops.php?type=saveadds1&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });
 
}
saveadds2Form(data){
  let temp = data.value;
 
 this.service.post('sops.php?type=saveadds2&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });
 
}

saveadds3Form(data){
  let temp = data.value;
 
 this.service.post('sops.php?type=saveadds3&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });
 
}
saveadds4Form(data){
  let temp = data.value;
 
 this.service.post('sops.php?type=saveadds4&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });
 
}
saveadds5Form(data){
  let temp = data.value;
 
 this.service.post('sops.php?type=saveadds5&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });
 
}


saveDefination(data){
 let temp = data.value;

 this.service.post('sops.php?type=saveDefination&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });
 
}

saveabbreviationForm(data){

 let temp = data.value
 // temp['abbreviation'] = this.abbreviationList;

 this.service.post('sops.php?type=saveabbreviationForm&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });
 
}


savetrainingList(data){
 let temp = data.value;
 // temp['training'] = this.trainingList;
 this.service.post('sops.php?type=savetrainingList&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 }); 
}


savedistributionList(data){
 let temp =data.value;

 this.service.post('sops.php?type=savedistributionList&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });

}


saverevisionList(data){
 let temp = data.value;



 this.service.post('sops.php?type=saverevisionList&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });
 
}

saveAssDocument(data){
 // let temp =  {};
 // temp['reference'] = {};
  
 // temp['reference']['associatedList'] = this.associatedList;
 // temp['reference']['externalList'] = this.externalList;
 let temp = data.value;
 this.service.post('sops.php?type=saveAssDocument&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });

}

saverolesform(data){
 // let temp =  {};
 // temp['roles'] = this.roleList;
 let temp = data.value;

 this.service.post('sops.php?type=saverolesform&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });

}



savepurpose(data){

let temp = data.value;
this.service.post('sops.php?type=savePurpose&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
 if (response['status'] == 'success') {
   alert('Saved Successfully');
   } else {
   alert('Failed: An error occured, please try again!');
 }
});

}


saveprocedure(data){

 let temp = data.value;

 this.service.post('sops.php?type=saveprocedure&ID='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
   if (response['status'] == 'success') {
     alert('Saved Successfully');
     } else {
     alert('Failed: An error occured, please try again!');
   }
 });

}

  }

// }
