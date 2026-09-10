import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ContentMasterLogComponent } from './content-master-log.component';

describe('ContentMasterLogComponent', () => {
  let component: ContentMasterLogComponent;
  let fixture: ComponentFixture<ContentMasterLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ContentMasterLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ContentMasterLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
