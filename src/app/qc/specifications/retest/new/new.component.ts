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
    this.getPendingRetestRawSpecification();
  }

  getPendingRetestRawSpecification() {
    this.service.get('qa.php?type=getPendingRetestRawSpecification').subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.isViewSpecification = true;
    this.selectedSpec = this.specifications[index];
  }

  updateTests(index, value) {
    let data = this.selectedSpec['spec_tests'];
    if (value == 0) {
      data[index].retest = 'yes';
    } else {
      data[index].retest = 'no';
    }
    this.selectedSpec['spec_tests'] = data;
  }




  save() {
  
    

    this.service.post('qa.php?type=saveRetestSpecification', JSON.stringify(this.selectedSpec)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isViewSpecification = false;
        this.getPendingRetestRawSpecification();
        alertify.success('Retest Specification saved successfully');
        this.router.navigate(['/specificationReport']);
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }

}
