/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { Daily_newComponent } from './daily_new.component';

describe('Daily_newComponent', () => {
  let component: Daily_newComponent;
  let fixture: ComponentFixture<Daily_newComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ Daily_newComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(Daily_newComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
