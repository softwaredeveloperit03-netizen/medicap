import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VisitorManagementComponent } from './visitor-management.component';

describe('VisitorManagementComponent', () => {
  let component: VisitorManagementComponent;
  let fixture: ComponentFixture<VisitorManagementComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ VisitorManagementComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(VisitorManagementComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
