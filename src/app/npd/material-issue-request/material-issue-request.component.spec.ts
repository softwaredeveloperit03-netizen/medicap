import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MaterialIssueRequestComponent } from './material-issue-request.component';

describe('MaterialIssueRequestComponent', () => {
  let component: MaterialIssueRequestComponent;
  let fixture: ComponentFixture<MaterialIssueRequestComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MaterialIssueRequestComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MaterialIssueRequestComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
