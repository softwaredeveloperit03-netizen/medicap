/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { New_correctionComponent } from './new_correction.component';

describe('New_correctionComponent', () => {
  let component: New_correctionComponent;
  let fixture: ComponentFixture<New_correctionComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ New_correctionComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(New_correctionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
