import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MyjobresComponent } from './myjobres.component';

describe('MyjobresComponent', () => {
  let component: MyjobresComponent;
  let fixture: ComponentFixture<MyjobresComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MyjobresComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MyjobresComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
