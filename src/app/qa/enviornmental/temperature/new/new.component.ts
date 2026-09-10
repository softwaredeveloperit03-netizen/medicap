import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  departments;
  sections;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
  }
  
  getDepartments() {
    this.service.get('qa/temperature.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getSections(index) {
    this.sections = this.departments[index].sections;
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('qa/temperature.php?type=saveTemperature', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record saved successfully');
        data.resetForm();
        this.router.navigate(['/enviornmental/temperature']);
      } else {
        alert(response['status']);
      }
    });
  }

}
