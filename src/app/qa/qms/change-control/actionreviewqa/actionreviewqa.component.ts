import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;



@Component({
  selector: 'app-actionreviewqa',
  templateUrl: './actionreviewqa.component.html',
  styleUrls: ['./actionreviewqa.component.css'],
})
export class ActionreviewqaComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getCcForActionAndReviewByQA();
  }

  typeCritical = '';
  typeMajor = '';

  results;
  isView = false;

  getCcForActionAndReviewByQA() {
    this.service
      .get(
        'changecontrol1.php?type=getCcForActionAndReviewByQAMeha&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  selectedResult = [];

  view(i) {
    this.selectedResult = this.results[i];
    this.isView = true;
  }
  documentChangeData = [];

  addDocumentChange(data) {
    if (!data.valid) {
      alert('All Fields Are Required!!!!');
      return;
    }
    let temp = data.value;
    this.documentChangeData.push(temp);
    data.reset();
  }

  delDocumentChange(i) {
    this.documentChangeData.splice(i, 1);
  }

  viewDevDoc(url) {
    url = this.service.url + '../../upload/changeControl/' + url;
    window.open(url, '_blank');
  }

  equipmentChangeData = [];

  addEquipmentChange(data) {
    if (!data.valid) {
      alert('All Fields Are Required!!!!');
      return;
    }
    let temp = data.value;
     console.log(temp);
    this.equipmentChangeData.push(temp);
    console.log(this.equipmentChangeData);
    data.reset();
  }

  delEquipmentChange(i) {
    this.equipmentChangeData.splice(i, 1);
  }
  documentEquipmentData = [];

  addDocumentEquipment(data) {
    if (!data.valid) {
      alert('All Fields Are Required!!!!');
      return;
    }
    let temp = data.value;
    this.documentEquipmentData.push(temp);
    data.reset();
  }

  delDocumentEquipment(i) {
    this.documentEquipmentData.splice(i, 1);
  }

  consentRevDoc: File;
  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.consentRevDoc = event.target.files[0];
    }
  }

  update(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    const temp = data.value;

    temp['documentChangeData'] = this.documentChangeData;
    temp['equipmentChangeData'] = this.equipmentChangeData;
     temp['documentEquipmentData'] = this.documentEquipmentData;
    temp['id'] = this.selectedResult['id'];
    temp['ccNo'] = this.selectedResult['ctrl_no'];
    temp['deptName'] = localStorage.getItem('department');

    this.service
      .post(
        'changecontrol1.php?type=saveMehaQaRevieweAndActions',
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alert('Saved Successfully !!!!!!');
          this.getCcForActionAndReviewByQA();
          data.resetForm();
          this.isView = false;
          this.selectedResult = [];
        } else {
          alert('Failed: An error occurred, please try again!');
        }
      });
  }
}
