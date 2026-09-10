import { Component, OnInit,VERSION, ViewChild } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service'; 
import * as ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { CKEditor5, CKEditorComponent } from '@ckeditor/ckeditor5-angular';
@Component({
  selector: 'app-draft',
  templateUrl: './draft.component.html',
  styleUrls: ['./draft.component.css']
})
export class DraftComponent implements OnInit {
  @ViewChild("myckeditor") ckeditor: CKEditorComponent;
  name = 'ng2-ckeditor';
  mycontent: string;
  log: string = '';
  ckeConfig: CKEditor5.Config;

  isView = false;
  results;
  selectedResult = [];

  overview: File;
  editor = ClassicEditor;
  data: any = '';

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
  
  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router) {
    
  }

  ngOnInit(): void {
    this.ckeConfig = {
      allowedContent: false,
      extraPlugins: 'divarea',
      forcePasteAsPlainText: true,
      removePlugins: 'exportpdf'
    };
    this.route.params.subscribe(params => {
      if (params['id'] == 0) {
        this.getpendingdraft();
      } else {
        this.getpendingdraftrecord(params['id']);
      }
    });
    this.getDepartments();
  }

  getpendingdraft() {
    this.service.get('sops.php?type=getpendingdraft').subscribe(response => {
      this.results = response;
    });
  }

  getpendingdraftrecord(id) {
    this.service.get('sops.php?type=getpendingdraftrecord&id=' + id).subscribe((response: any) => {
      this.selectedResult = response;
      let temp = {};
      temp['role'] = "Officer " + this.selectedResult['department'];
      temp['responsibility'] = "Responsible for preparation of SOP";
      this.roleList[this.roleList.length] = temp;

      temp = {};
      temp['role'] = "Manager " + this.selectedResult['department'];
      temp['responsibility'] = "Responsible for Review and check the SOP";
      this.roleList[this.roleList.length] = temp;

      temp = {};
      temp['role'] = "Head QA";
      temp['responsibility'] = "Responsible for review and approval of SOP";
      this.roleList[this.roleList.length] = temp;

      this.isView = true;
    });
  }

  close() {
    this.getpendingdraft();
    this.isView = false;
  }

  view(index) {
    this.selectedResult = this.results[index];

    let temp = [];
    temp['role'] = "Officer " + this.selectedResult['department'];
    temp['responsibility'] = "Responsible for preparation of SOP";
    this.roleList[this.roleList.length] = temp;

    temp = [];
    temp['role'] = "Manager " + this.selectedResult['department'];
    temp['responsibility'] = "Responsible for Review and check the SOP";
    this.roleList[this.roleList.length] = temp;

    temp = [];
    temp['role'] = "Head QA";
    temp['responsibility'] = "Responsible for review and approval of SOP";
    this.roleList[this.roleList.length] = temp;

    this.isView = true;
  }

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
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

  onFileChanged1(event) {
    if (event.target.files.length !== 0) {
      this.overview = event.target.files[0];
    }
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    const formData = new FormData();
 
    for (var i = 0; i < this.myFiles.length; i++) { 
      formData.append("file-" + i, this.myFiles[i]);
    }

    let temp = data.value;
    for (let key in temp) {
      let value = temp[key];
      // Use `key` and `value`
      formData.append(key, value);
    }

    if (this.overview !== undefined) {
      formData.append('overview', this.overview, this.overview.name);
    } else {
      alert('Process Overview is Required');
      return;
    }

    formData.append("distribution", JSON.stringify(this.distributionList));
    formData.append("revision", JSON.stringify(this.revisionList));
    formData.append("role", JSON.stringify(this.roleList));
    formData.append("definition", JSON.stringify(this.definitionList));
    formData.append("training", JSON.stringify(this.trainingList));
    formData.append("external", JSON.stringify(this.externalList));
    formData.append("associated", JSON.stringify(this.associatedList));
    formData.append("abbreviation", JSON.stringify(this.abbreviationList));
    this.service.post('sops.php?type=savedraft&id=' + this.selectedResult['id'], formData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('saved successfully');
        this.distributions = [];
        this.revisionList = [];
        this.roleList = [];
        this.definitionList = [];
        this.distributionList = [];
        this.trainingList = [];
        this.externalList = [];
        this.associatedList = [];
        this.abbreviationList = [];
        data.resetForm();
        this.router.navigate(['/sop/log/']);
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
  test=(event)=>{
    console.log(event.keyCode);
  }
  onChange($event: any): void {
    console.log("onChange");
    //this.log += new Date() + "<br />";
  }

  onPaste($event: any): void {
    console.log("onPaste");
    //this.log += new Date() + "<br />";
  }
}
