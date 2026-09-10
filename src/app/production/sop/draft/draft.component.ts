import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-draft',
  templateUrl: './draft.component.html',
  styleUrls: ['./draft.component.css']
})
export class DraftComponent implements OnInit {

  isView = false;
  results;
  selectedResult = [];

  init = ({
    height: 300,
    menubar: true,
    content_style: 'body { font-size: 12pt; font-family: Times; }',
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

  cdepartments;
  departments;

  distributions = [];
  revisionList = [];
  roleList = [];
  distributionList = [];
  trainingList = [];

  isImpactQuality = false;
  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
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

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;
    temp['role'] = this.roleList;
    temp['training'] = this.trainingList;
    temp['distribution'] = this.distributionList;
    temp['revision'] = this.revisionList;
    this.service.post('sops.php?type=savedraft&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('saved successfully');
        this.roleList = [];
        data.resetForm();
        this.router.navigate(['/sop/changecontrol/' + this.selectedResult['id']]);
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

}
