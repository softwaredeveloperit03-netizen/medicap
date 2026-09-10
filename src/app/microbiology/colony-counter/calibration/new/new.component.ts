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
  ids;
  times_pressed =[];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getColonyCounters();
  }
  getColonyCounters(){
    this.service.get('equipments.php?type=getColonyCounters').subscribe(response =>{
      this.ids =response;
    });
  }
  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.times_pressed[this.times_pressed.length] = temp;
    data.resetForm();
  }

  del(index) {
    this.times_pressed.splice(index, 1);
  }
  
  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (this.times_pressed.length == 0) {
      alertify.error('times_pressed are required');
      return;
    }
    let temp = data.value;
    temp['details'] = this.times_pressed; 
    this.service.post('microbiology/colonycounter.php?type=saveCalibration', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully');
        data.resetForm();
        this.times_pressed = [];
        this.router.navigate(['/microbiology/colony-counter/calibraion']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
