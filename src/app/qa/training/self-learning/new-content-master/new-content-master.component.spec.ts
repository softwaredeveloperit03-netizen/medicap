import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewContentMasterComponent } from './new-content-master.component';

describe('NewContentMasterComponent', () => {
  let component: NewContentMasterComponent;
  let fixture: ComponentFixture<NewContentMasterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewContentMasterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewContentMasterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
