import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-annoucement',
  templateUrl: './annoucement.component.html',
  styleUrls: ['./annoucement.component.css']
})
export class AnnoucementComponent implements OnInit {

  trainers = [];
  labours;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getTrainers();
    this.getLabours();
  }

  getTrainers() {
    this.service.get('training.php?type=getTrainers').subscribe((response: any) => {
      this.trainers = response;
    });
  }

  getLabours() {
    this.service.get('training.php?type=getLabours').subscribe(response => {
      this.labours = response;
    });
  }

  change(value, index) {
    this.labours[index].status = value;
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;

    let participants = [];
    for (let i = 0; i < this.labours.length; i++) {
      let labour = this.labours[i];
      if (labour['status'] == 'true') {
        participants[participants.length] = labour;
      }
    }
    temp['participants'] = participants;
    this.service.post('training.php?type=saveDailyAnnoucement', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
