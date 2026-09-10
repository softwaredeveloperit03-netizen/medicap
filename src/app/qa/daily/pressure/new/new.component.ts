import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  depart;
  sections;
  router: any;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getDepartment();
  }
  getDepartment() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.depart = response;
    });
  }
  getSections(index) {
    index = index - 1;
    if (index !== -1) {
      let temp = this.depart[index];
      this.sections = temp['sections'];
    }
  }
  // save(data) {
  //   if (data.valid) {
  //   this.service.post('qa/pressure.php?type=saveDailyPressure', JSON.stringify(data.value)).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       alert("saved succesfully");
  //       data.reset();
  //     }
  //   });
  // }
  //   else{
  //     alert("Failed an error occured")
  //   }
  // }
  save(Form) {
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service
      .post(
        'qa/pressure.php?type=saveDailyPressure',
        JSON.stringify(Form.value)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          // this.router.navigate(['/hr/asset']);
          alertify.success('data save Successfuly');
          Form.resetForm();
        } else {
          alertify.error('Error Occured');
        }
      });
  }
  close() {
    this.router.navigate(['/qa/daily']);
  }
}
