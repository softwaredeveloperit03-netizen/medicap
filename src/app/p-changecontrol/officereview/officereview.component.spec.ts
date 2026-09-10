import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OfficereviewComponent } from './officereview.component';

describe('OfficereviewComponent', () => {
  let component: OfficereviewComponent;
  let fixture: ComponentFixture<OfficereviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OfficereviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OfficereviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
