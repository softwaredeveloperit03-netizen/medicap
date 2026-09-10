import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-direct',
  templateUrl: './direct.component.html',
  styleUrls: ['./direct.component.css']
})
export class DirectComponent implements OnInit {

  init = ({
    height: 300,
    menubar: true,
    content_style: 'body { font-size: 12pt; font-family: Times; } ol{counter-reset: item}ol > li{display: block}ol > li:before {content: counters(item, ".") ". ";counter-increment: item}',
    plugins: [
      'advlist autolink lists link image charmap print preview anchor',
      'searchreplace visualblocks code fullscreen',
      'insertdatetime media table paste code help wordcount'
    ],
    toolbar:
      'formatselect | bold italic backcolor | \
      alignleft aligncenter alignright alignjustify | \
      bullist numlist outdent indent | removeformat | help'
  });
  api = 'lh5ymzb4rorw2zhscucerx1013ntad53j7jjnoiokc0pjg8v';

  departments;
  isEquipment = false;
  isOther = false;
  equipments;
  designations;

  distributions = [];
  revisionList = [];
  roleList = [];
  definitionList = [];
  distributionList = [];
  trainingList = [];
  externalList = [];
  associatedList = [];

  myFiles:string [] = [];
  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit(): void {
    this.getDepartments();
    this.getDesignations();
  }

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getDesignations() {
    this.service.get('sops.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }

  checkFor(value) {
    if (value == 'Equipment') {
      this.isEquipment = true;
      this.isOther = false;
      this.getEquipments();
    } else if (value == 'Other') {
      this.isOther = true;
      this.isEquipment = false;
    } else {
      this.isOther = false;
      this.isEquipment = false;
    }
  }

  getEquipments() {
    this.service.get('equipments.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
    });
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
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

    formData.append("distribution", JSON.stringify(this.distributionList));
    formData.append("revision", JSON.stringify(this.revisionList));
    formData.append("role", JSON.stringify(this.roleList));
    formData.append("definition", JSON.stringify(this.definitionList));
    formData.append("training", JSON.stringify(this.trainingList));
    formData.append("external", JSON.stringify(this.externalList));
    formData.append("associated", JSON.stringify(this.associatedList));
    this.service.post('sops.php?type=saveDirectSOP', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(this.service.t('common.savedSuccess'));
        this.distributions = [];
        this.revisionList = [];
        this.roleList = [];
        this.definitionList = [];
        this.distributionList = [];
        this.trainingList = [];
        this.externalList = [];
        this.associatedList = [];
        data.resetForm();
        this.router.navigate(['/sop/log/']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  onFileChange(event) {
    for (var i = 0; i < event.target.files.length; i++) { 
        this.myFiles.push(event.target.files[i]);
    }
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

  deleteRevision(index) {
    this.revisionList.splice(index, 1);
  }

  addDistribution(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
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
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.trainingList[Object.keys(this.trainingList).length] = temp['department'];
    data.resetForm();
  }

  deleteTraining(index) {
    this.trainingList.splice(index, 1);
  }

  addDesignation(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.roleList[Object.keys(this.roleList).length] = temp;
    data.resetForm();
  }

  deleteDesignation(index) {
    this.roleList.splice(index, 1);
  }

  addDefinition(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
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
      alertify.error('All fields are required');
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
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.associatedList[Object.keys(this.associatedList).length] = temp;
    data.resetForm();
  }

  deleteAssociated(index) {
    this.associatedList.splice(index, 1);
  }

}
