import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  specifications;
  isViewSpecification = false;
  selectedSpec = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getFPSpecifications();
  }

  getFPSpecifications() {
    this.service.get('qa.php?type=getPendingStabilitySpecifications').subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.selectedSpec = this.specifications[index];
    this.isViewSpecification = true;

    for (let i = 0; i < this.selectedSpec['spec_tests'].length; i++) {
      this.selectedSpec['spec_tests'][i].release_stability = false;
  }
  console.log('this.selectedSpec :>> ', this.selectedSpec['spec_tests']);
  this.selectedUsers=[];
    
  }
  // release_stability=false;
  updateTests(index, value) {
    let data = this.selectedSpec['spec_tests'];
    if (value == 0) {
      data[index].release_stability = 'Release';
    } else {
      data[index].release_stability = 'Stability';
    }
    this.selectedSpec['spec_tests'] = data;
  }

  save() {
    let temp=this.selectedSpec;
    temp['tests']=this.selectedUsers

    this.service.post('qa.php?type=saveStabilitySpecification', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stability Specification saved successfully');
        this.router.navigate(['/qc/specifications/stability']);
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }



  selectedUsers: any[] = [];

  onCheckboxChange(comp: any, event: any) {
    if (event.target.checked) {
        this.selectedUsers.push(comp);
    } else {
        const index = this.selectedUsers.findIndex(c => c === comp);
        if (index > -1) {
            this.selectedUsers.splice(index, 1);
        }
    }
    console.log(this.selectedUsers);
}

}
