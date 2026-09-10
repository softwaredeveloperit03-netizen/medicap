import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SanewComponent } from './sanew.component';

describe('SanewComponent', () => {
  let component: SanewComponent;
  let fixture: ComponentFixture<SanewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SanewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SanewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
