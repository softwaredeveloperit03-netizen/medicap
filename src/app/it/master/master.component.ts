import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-master',
  templateUrl: './master.component.html',
  styleUrls: ['./master.component.css']
})
export class MasterComponent implements OnInit {
  selectedFile2: any;
  roll = '';
  designation = '';
  constructor(private service: DataAccessService,private router: Router ) {}


  ngOnInit(): void {
  }


  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('it/itall.php?type=savemasterloginform',JSON.stringify(data.value)).subscribe(response => {
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
