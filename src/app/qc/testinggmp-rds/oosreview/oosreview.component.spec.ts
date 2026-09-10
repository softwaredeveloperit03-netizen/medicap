import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OosreviewComponent } from './oosreview.component';

describe('OosreviewComponent', () => {
  let component: OosreviewComponent;
  let fixture: ComponentFixture<OosreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OosreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OosreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
