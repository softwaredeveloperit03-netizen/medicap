import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CheckshiftComponent } from './checkshift.component';

describe('CheckshiftComponent', () => {
  let component: CheckshiftComponent;
  let fixture: ComponentFixture<CheckshiftComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CheckshiftComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CheckshiftComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
