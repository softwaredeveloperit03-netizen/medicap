import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ApprovalofchangeComponent } from './approvalofchange.component';

describe('ApprovalofchangeComponent', () => {
  let component: ApprovalofchangeComponent;
  let fixture: ComponentFixture<ApprovalofchangeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ApprovalofchangeComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ApprovalofchangeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
