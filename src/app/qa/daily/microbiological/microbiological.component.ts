import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-microbiological',
  templateUrl: './microbiological.component.html',
  styleUrls: ['./microbiological.component.css']
})
export class MicrobiologicalComponent implements OnInit {
  

  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit(): void {
  }

  // save(data) {
  //    this.service.post('qa/temperature.php?type=savemacro',JSON.stringify(data)).subscribe(response=>{
  //     alert("saved succesfully")
  //     this.router.navigate(['/'])
  //     data.resetForm();

  //   });
    
  // }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    this.service.post('qa/temperature.php?type=savemacro',JSON.stringify(data)).subscribe(response=>{
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        // this.router.navigate(['/checklist']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }
}

