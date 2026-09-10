import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HeadApprovalComponent } from './head-approval.component';

describe('HeadApprovalComponent', () => {
  let component: HeadApprovalComponent;
  let fixture: ComponentFixture<HeadApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ HeadApprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HeadApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
