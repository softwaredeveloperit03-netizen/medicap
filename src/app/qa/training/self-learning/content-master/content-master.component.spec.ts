import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ContentMasterComponent } from './content-master.component';

describe('ContentMasterComponent', () => {
  let component: ContentMasterComponent;
  let fixture: ComponentFixture<ContentMasterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ContentMasterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ContentMasterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
