import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-changecontrol',
  templateUrl: './changecontrol.component.html',
  styleUrls: ['./changecontrol.component.css']
})
export class ChangecontrolComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  cdepartments;

  isImpactQuality = false;
  constructor(private service: DataAccessService, public route: ActivatedRoute, private router: Router) { }

  ngOnInit() {
    this.route.params.subscribe(params => {
      if (params['id'] == 0) {
        this.getPendingChangeControls();
      } else {
        this.getPendingChangeControlsRecords(params['id']);
      }
    });
    this.getChangeDepartments();
  }

  getPendingChangeControls() {
    this.service.get('sops.php?type=getPendingChangeControls').subscribe(response => {
      this.results = response;
    });
  }

  getPendingChangeControlsRecords(id) {
    this.service.get('sops.php?type=getPendingChangeControlsRecords&id=' + id).subscribe((response: any) => {
      this.selectedResult = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  getChangeDepartments() {
    this.service.get('changecontrol.php?type=getDepartments').subscribe((response:any) =>{
      this.cdepartments =  response;
    });
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.cdepartments.length; i++) {
      let department = this.cdepartments[i];
      if (department['status']) {
        test[test.length] = department['department_name'];
      }
    }

    temp['departments'] = test;
    this.service.post('sops.php?type=saveInitiatedChangeControl&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('saved successfully');
        data.resetForm();
        this.router.navigate(['/sop/initiate-log/']);
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  updateDept(value, i) {
    this.cdepartments[i].status = value;
  } 

  checkImpactQuality(value) {
    if (value === 'Yes') {
      this.isImpactQuality = true;
    } else {
      this.isImpactQuality = false;
    }
  }

}
