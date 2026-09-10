import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  contracts: any[] = [];
  loading = false;
  isView = false;
  selectedContract: any = {};

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getContracts();
  }

  getContracts() {
    this.loading = true;
    this.service.get('account/prc.php?type=getContracts').subscribe((response: any) => {
      this.contracts = response || [];
      this.loading = false;
    }, error => {
      console.error('Error fetching contracts:', error);
      alertify.error('Error fetching contracts');
      this.loading = false;
    });
  }

  view(index: number) {
    this.selectedContract = this.contracts[index];
    this.isView = true;
  }

  edit(index: number) {
    const contract = this.contracts[index];
    // Navigate to contract form with ID for editing
    this.router.navigate(['/accounts/prc/contract'], { 
      queryParams: { id: contract.id } 
    });
  }

  delete(index: number) {
    const contract = this.contracts[index];
    alertify.confirm('Are you sure you want to delete this contract?', () => {
      this.service.get('account/prc.php?type=deleteContract&id=' + contract.id).subscribe((response: any) => {
        if (response['status'] === 'success') {
          alertify.success('Contract deleted successfully');
          this.getContracts();
        } else {
          alertify.error(response['status'] || 'Error deleting contract');
        }
      }, error => {
        console.error('Error deleting contract:', error);
        alertify.error('Error deleting contract');
      });
    });
  }

  closeView() {
    this.isView = false;
    this.selectedContract = {};
  }

  refresh() {
    this.getContracts();
  }
}

