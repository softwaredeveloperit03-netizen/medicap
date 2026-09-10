import { Component, OnInit } from '@angular/core';
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
  departments;
  isImpactQuality = false;
  url = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getInprocessRevisionRequests();
  }

  getInprocessRevisionRequests() {
    this.service.get('sops.php?type=getInprocessRevisionRequests').subscribe(response => {
      this.results = response;
    });
  }

  getDepartments() {
    this.service.get('changecontrol.php?type=getDepartments').subscribe((response:any) =>{
      this.departments =  response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.url = this.service.url + 'pdf1/sop.php?type=sopdigital&sop_no=' + this.selectedResult['sop_no'] + '&token=' + localStorage.getItem('token');
    this.isView = true;
    this.getDepartments();
  }

  checkImpactQuality(value) {
    if (value === 'Yes') {
      this.isImpactQuality = true;
    } else {
      this.isImpactQuality = false;
    }
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedResult['id'];
    temp['sop_no'] = this.selectedResult['sop_no'];
    this.service.post('sops.php?type=revisionChangeControl', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Change Control Request send for Approval');
        this.isView = false;
        this.getInprocessRevisionRequests();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
