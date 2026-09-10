/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { Index_masterComponent } from './index_master.component';

describe('Index_masterComponent', () => {
  let component: Index_masterComponent;
  let fixture: ComponentFixture<Index_masterComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ Index_masterComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(Index_masterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
