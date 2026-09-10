import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ClientApprovalComponent } from './client-approval.component';

describe('ClientApprovalComponent', () => {
  let component: ClientApprovalComponent;
  let fixture: ComponentFixture<ClientApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ClientApprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ClientApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
