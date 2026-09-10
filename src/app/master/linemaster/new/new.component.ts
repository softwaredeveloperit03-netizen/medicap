import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  lineName: string = '';
  savedLines: any[] = [];
  isLoading: boolean = false;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getLines();
  }

  getLines(): void {
    this.isLoading = true;
    this.service.get('bmr/process.php?type=getLines').subscribe((response: any) => {
      this.savedLines = Array.isArray(response) ? response : [];
      this.isLoading = false;
    }, error => {
      console.error('Error fetching lines:', error);
      alertify.error('Error loading lines');
      this.isLoading = false;
    });
  }

  saveLine(): void {
    if (!this.lineName.trim()) {
      alertify.error('Please enter a line name');
      return;
    }

    const lineData = {
      line_name: this.lineName.trim()
    };

    this.isLoading = true;
    this.service.post('bmr/process.php?type=saveLine', JSON.stringify(lineData)).subscribe(response => {
      this.isLoading = false;
      if (response['status'] === 'success') {
        alertify.success('Line saved successfully');
        this.lineName = '';
        this.getLines(); // Refresh the list
      } else {
        alertify.error(response['message'] || 'Error saving line');
      }
    }, error => {
      console.error('Error saving line:', error);
      alertify.error('Error saving line');
      this.isLoading = false;
    });
  }

  deleteLine(line: any): void {
    if (confirm('Are you sure you want to delete this line?')) {
      this.isLoading = true;
      this.service.get('bmr/process.php?type=deleteLine&id=' + line.id).subscribe(response => {
        this.isLoading = false;
        if (response['status'] === 'success') {
          alertify.success('Line deleted successfully');
          this.getLines(); // Refresh the list
        } else {
          alertify.error(response['message'] || 'Error deleting line');
        }
      }, error => {
        console.error('Error deleting line:', error);
        alertify.error('Error deleting line');
        this.isLoading = false;
      });
    }
  }
}
