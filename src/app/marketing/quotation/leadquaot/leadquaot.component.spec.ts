import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LeadquaotComponent } from './leadquaot.component';

describe('LeadquaotComponent', () => {
  let component: LeadquaotComponent;
  let fixture: ComponentFixture<LeadquaotComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LeadquaotComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LeadquaotComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
