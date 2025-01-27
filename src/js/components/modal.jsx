import React, { useCallback, useState, useEffect } from "react";
import { FocalPointPicker } from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";

const PortalContent = ({ onClose, mediaId }) => {
  console.log("PortalContent", mediaId);

  const [loading, setLoading] = useState(false);
  const [focalPoint, setFocalPoint] = useState({ x: 0.5, y: 0.5 });
  const [hasChanges, setHasChanges] = useState(false);
  const [media, setMedia] = useState(null);

  const mediaFetch = useCallback(async () => {
    await apiFetch({
      path: `/wp/v2/media/${mediaId}`,
    }).then((response) => {
      // Media
      setMedia(response);
      setFocalPoint(
        response.meta.focal_point || {
          x: 0.5,
          y: 0.5,
        }
      );
      setLoading(false);
    });
  }, [mediaId]);

  const focalPointSave = useCallback(async () => {
    // Send the

    await apiFetch({
      path: `/wp-target-crop/v1/focal-point/${mediaId}`,
      method: "POST",
      data: {
        focalPoint,
      },
    }).then((response) => {
      if (response) {
        onClose();
      } else {
        // Handle error
        alert("Error: Focus point not saved");
      }

      console.log(response);
    });
  }, [focalPoint, mediaId, onClose]);

  useEffect(async () => {
    if (loading) {
      return;
    }

    setLoading(true);

    await mediaFetch();
  }, [mediaId]);

  if (!media || loading) {
    return null;
  }

  return (
    <div
      style={{
        position: "fixed",
        top: 0,
        left: 0,
        width: "100%",
        height: "100%",
        backgroundColor: "rgba(0, 0, 0, 0.5)",
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        zIndex: 9999999,
      }}
      onClick={onClose} // Close the portal when clicking outside
    >
      <div
        className="relative bg-white w-full h-auto p-10 max-w-3xl max-h-3xl"
        onClick={(e) => e.stopPropagation()} // Prevent closing when clicking inside
      >
        <FocalPointPicker
          url={media.source_url}
          dimensions={{
            width: media.media_details.width,
            height: media.media_details.height,
          }}
          value={focalPoint}
          onChange={(focalPoint) => {
            setFocalPoint(focalPoint);
            setHasChanges(true);
          }}
        />

        <div className="flex gap-x-2">
          <button
            className="button button-primary button-large"
            onClick={focalPointSave}
            disabled={!hasChanges}
          >
            Save
          </button>
          <button className="button button-large" onClick={onClose}>
            Close
          </button>
        </div>
      </div>
    </div>
  );
};

export default PortalContent;
