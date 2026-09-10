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
  }

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
    this.service.post('qa.php?type=saveStabilitySpecification', JSON.stringify(this.selectedSpec)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Stability Specification saved successfully');
        this.router.navigate(['/specificationReport']);
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }

}
