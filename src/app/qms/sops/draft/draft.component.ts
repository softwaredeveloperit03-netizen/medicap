import { Component, OnInit, SecurityContext } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DomSanitizer } from '@angular/platform-browser';
@Component({
  selector: 'app-draft',
  templateUrl: './draft.component.html',
  styleUrls: ['./draft.component.css']

})

export class DraftComponent implements OnInit {






















  
  editorContent: string = ''; // Declare the editorContent property

  // onInput(event: any): void {
  //   // Update editorContent when the content changes
  //   this.editorContent = event.target.innerHTML;
  // }

  applyFormatting(command: string): void {
    document.execCommand(command, false, null);
  }

  applyList(type: string): void {
    document.execCommand('insert' + type + 'list', false, null);
  }

  increaseIndent(): void {
    document.execCommand('indent', false, null);
  }

  decreaseIndent(): void {
    document.execCommand('outdent', false, null);
  }
  








  purposetitle ;
  scopetitle ;
  RolesRestitle ;  
  definitiontitle ;
  externaltitle ;  
  Processtitle ; 
  Proceduretitle ;
  Abbreviationtitle ;
  Trainingtitle ; 
  Distributiontitle ;
  Attachmentstitle ;
  Revisiontitle ; 
  add1_title;
  add2_title;
  add3_title;
  add4_title;
  add5_title;

  

  isView = false;
  results;
  selectedResult = [];
  // formattedPurpose: SafeHtml = ''
  overview: File;
  attachments: File;

  cdepartments;
  departments;
  distributions = [];
  revisionList = [];
  roleList = [];
  distributionList = [];
  trainingList = [];
  definitionList = [];
  externalList = [];
  associatedList = [];
  abbreviationList = [];
  isImpactQuality = false;
  myFiles:string [] = [];
  sanitizedEditorData: any;
  purpose;
  scope;
  role;
  defination;
  reference;
  process;
  procedures;
  abbreviation;
  training;
  distribution;
  revision;
  

  // init = ({
  //   height: 300,
  //   menubar: true,
  //   statusbar: false,
  //   content_style: 'body { font-size: 12pt; font-family: Times; } ol{counter-reset: item}ol > li{display: block}ol > li:before {content: counters(item, ".") ". ";counter-increment: item}',
  //   plugins: [
  //     'advlist autolink lists link image charmap print preview anchor table'
  //   ],
  //   toolbar:
  //     'formatselect | bold italic backcolor | \
  //     alignleft aligncenter alignright alignjustify | \
  //     bullist numlist outdent indent | removeformat | table'
  // });
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
  
  api = '9boizsxuop2gshu192rv00t5xs8z7u7o6ohvpgswgy1qkida';


   // Property to hold the formatted purpose
    constructor(private service: DataAccessService, private sanitizer: DomSanitizer,public route: ActivatedRoute, private router: Router) {
 
    }
   
  ngOnInit() {
    this.route.params.subscribe(params => {
      if (params['id'] == 0) {
        this.getpendingdraft();
      } else {
        this.getpendingdraftrecord(params['id']);
      }
    });
    this.getDepartments();
    this.get_format();
    
  }
  formats;
  get_format() {
    this.service.get('sops1.php?type=get_format').subscribe(response => {
      this.formats = response;
    });
  }

  

   formattedPurpose: string = '';
  formatHTMLContent() {
    const parser = new DOMParser();
    const doc = parser.parseFromString(this.selectedResult['purpose'], 'text/html');
    let formattedHtml = '';

    const formatNode = (node: Element, numbering: string) => {
      if (node.tagName.toLowerCase() === 'li') {
        formattedHtml += numbering + ' ' + (node.textContent || '') + '<br>';
      }
      for (let i = 0; i < node.children.length; i++) {
        const childNode = node.children[i];
        if (childNode.tagName.toLowerCase() === 'ol' || childNode.tagName.toLowerCase() === 'ul') {
          const listItems = childNode.querySelectorAll('li');
          for (let j = 0; j < listItems.length; j++) {
            const listItem = listItems[j];
            const newNumbering = numbering + '.' + (j + 1);
            formatNode(listItem, newNumbering);
          }
        }
      }
    };
    formatNode(doc.body, '1');
    // Sanitize and bind the formatted HTML content
    this.formattedPurpose = this.sanitizeHTML(formattedHtml);
  }
  formattedScope: string = ''; // Property to hold the formatted purpose
  formatHTMLContentScope() {
    const parser = new DOMParser();
    const doc = parser.parseFromString(this.selectedResult['scope'], 'text/html');
    let formattedHtmlScope = '';
    const formatNode = (node: Element, numbering: string) => {
      if (node.tagName.toLowerCase() === 'li') {
        formattedHtmlScope += numbering + ' ' + (node.textContent || '') + '<br>';
      }
      for (let i = 0; i < node.children.length; i++) {
        const childNode = node.children[i];
        if (childNode.tagName.toLowerCase() === 'ol' || childNode.tagName.toLowerCase() === 'ul') {
          const listItems = childNode.querySelectorAll('li');
          for (let j = 0; j < listItems.length; j++) {
            const listItem = listItems[j];
            const newNumbering = numbering + '.' + (j + 1);
            formatNode(listItem, newNumbering);
          }
        }
      }
    };
    formatNode(doc.body, '1');
     this.formattedScope = this.sanitizeHTML(formattedHtmlScope);
  }

  sanitizeHTML(html: string): string {
    return this.sanitizer.sanitize(SecurityContext.HTML, html) || '';
  }



    getDepartments() {
      this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
    }

  
 

    designations;

    getDesignation(data) {
      let department = data.value;
      for (let i=0; i< this.departments.length;i++){
        if(this.departments[i]['department_name'] == department){
          this.designations = this.departments[i]['designations'];
        }
      } 
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


   

  getpendingdraft() {
    this.service.get('sops.php?type=getpendingdraft&department1=Quality Assurance').subscribe(response => {
      this.results = response;  

      this.sanitizedEditorData = this.sanitizer.bypassSecurityTrustHtml(this.results[0]['purpose']); // Sanitize the data

      console.log("hi", this.results); 
    });
  }

  getpendingdraftrecord(id) {
    this.service.get('sops.php?type=getpendingdraftrecord&department1=Quality Assurance&id=' + id).subscribe((response: any) => {
      this.selectedResult = response;
      this.isView = true;
    });
  }

  close() {
    this.getpendingdraft();
    this.isView = false;
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
  adds1Array=[];
  adds2Array=[];
  adds3Array=[];
  adds4Array=[];
  adds5Array=[];


  view(index) {
   
    this.purpose=this.selectedResult['purpose']
    this.selectedResult = this.results[index];
    this.view_format=this.selectedResult['view_format'];
    console.log(this.view_format);
    console.log(this.selectedResult['purpose']);
    this.formatHTMLContent();
    this.formatHTMLContentScope();
    
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
    this.adds1Array = JSON.parse(this.selectedformats['adds1']);
    this.adds2Array = JSON.parse(this.selectedformats['adds2']);
    this.adds3Array = JSON.parse(this.selectedformats['adds3']);
    this.adds4Array = JSON.parse(this.selectedformats['adds4']);
    this.adds5Array = JSON.parse(this.selectedformats['adds5']);

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
    this.add1_title = this.adds1Array[0]?.['add1_title'];
    this.add2_title = this.adds2Array[0]?.['add2_title'];
    this.add3_title = this.adds3Array[0]?.['add3_title'];
    this.add4_title = this.adds4Array[0]?.['add4_title'];
    this.add5_title = this.adds5Array[0]?.['add5_title'];
 
 
    console.log(this.selectedformats); 
     this.isView = true;
  }

  
 



  onFileChanged1(event) {
    if (event.target.files.length !== 0) {
      this.overview = event.target.files[0];
    }
  }

  saveprocess_overview(){

    const uploadData = new FormData();
  
    if (this.overview !== undefined) {
      uploadData.append('doc', this.overview, this.overview.name);
    }
 
    this.service.post('sops.php?type=saveprocess_overview&ID='+this.selectedResult['id'], uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
    
  }


  onFileChanged2(event) {
    if (event.target.files.length !== 0) {
      this.attachments = event.target.files[0];
    }
  }

  saveattachments(){

    const uploadData = new FormData();
  
    if (this.attachments !== undefined) {
      uploadData.append('doc', this.attachments, this.attachments.name);
    }

 

    this.service.post('sops.php?type=saveattachments&ID='+this.selectedResult['id'], uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        } else {
        alert('Failed: An error occured, please try again!');
      }
    });
    
  }


  
  save(data) {
  
    let temp = "hii";
    this.service.post('sops.php?type=savedraft&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('saved successfully');

        this.isView = false;
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }









 

  addRevision(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.revisionList[Object.keys(this.revisionList).length] = temp;
    data.resetForm();
  }

  deleteRevision(index) {
    this.revisionList.splice(index, 1);
  }

  addDistribution(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.distributionList[Object.keys(this.distributionList).length] = temp['department'];
    data.resetForm();
  }

  deleteDistribution(index) {
    this.distributionList.splice(index, 1);
  }

  addTraining(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.trainingList[Object.keys(this.trainingList).length] = temp['department'];
    data.resetForm();
  }

  deleteTraining(index) {
    this.trainingList.splice(index, 1);
  }

  addDefinition(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.definitionList[Object.keys(this.definitionList).length] = temp;
    data.resetForm();
  }

  deleteDefinition(index) {
    this.definitionList.splice(index, 1);
  }


  addRoles(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.roleList[Object.keys(this.roleList).length] = temp;
    data.resetForm();
  }

  deleteRoles(index) {
    this.roleList.splice(index, 1);
  }

  addExternal(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.externalList[Object.keys(this.externalList).length] = temp;
    data.resetForm();
  }

  deleteExternal(index) {
    this.externalList.splice(index, 1);
  }

  addAssociated(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.associatedList[Object.keys(this.associatedList).length] = temp;
    data.resetForm();
  }

  deleteAssociated(index) {
    this.associatedList.splice(index, 1);
  }

  addAbbreviation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.abbreviationList[Object.keys(this.abbreviationList).length] = temp;
    data.resetForm();
  }

  deleteAbbreviation(index) {
    this.abbreviationList.splice(index, 1);
  }



  isShown: boolean = false; // hidden by default
  isdata: boolean;
  ispart: boolean;
  istable: boolean;
  ispara: boolean;
  isdiv: boolean;
  isrow: boolean;
  isreq: boolean;
  isdiv1: boolean;
  isreq1: boolean;
  isreq2: boolean;
  isShow1: boolean;
  isShow2: boolean;
  isShowad1: boolean;
  isShowad2: boolean;
  isShowad3: boolean;
  isShowad4: boolean;
  isShowad5: boolean;

  toggleShow() {
    this.isShown = !this.isShown;

  }
  dataShow() {
    this.isdata = !this.isdata;
  }
  valueShow() {
    this.ispart = !this.ispart;
  }
  systemShow() {
    this.istable = !this.istable;
  }
  indShow() {
    this.ispara = !this.ispara;
  }
  sysShow() {
    this.isdiv = !this.isdiv;
  }
  
  actualShow() {
    this.isrow = !this.isrow;
  }
  inputShow() {
    this.isreq = !this.isreq;
  }
  sysShow1(){
    this.isdiv1 = !this.isdiv1;
  }
  inputShow1(){
    this.isreq1 = !this.isreq1;
  }
  inputShow2(){
    this.isreq2 = !this.isreq2;
  }
  togShow(){

    this.isShow1 = !this.isShow1;
  }
  togShowad1(){

    this.isShowad1 = !this.isShowad1;
  }
  togShowad2(){

    this.isShowad2 = !this.isShowad2;
  }
  togShowad3(){

    this.isShowad3 = !this.isShowad3;
  }
  togShowad4(){

    this.isShowad4 = !this.isShowad4;
  }
  togShowad5(){

    this.isShowad5 = !this.isShowad5;
  }
 
  toShow(){

    this.isShow2 = !this.isShow2;
  }



































































































































  checkImpactQuality(value) {
    if (value === 'Yes') {
      this.isImpactQuality = true;
    } else {
      this.isImpactQuality = false;
    }
  }

  updateDept(value, i) {
    this.cdepartments[i].status = value;
  }











}



